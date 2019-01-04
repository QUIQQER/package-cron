<?php

/**
 * This file contains QUI\Cron\Events
 */

namespace QUI\Cron;

use QUI;

/**
 * Cron Main Events
 *
 * @author www.pcsg.de (Henning Leutz)
 */
class EventHandler
{
    /**
     * event: onPackageSetup
     *
     * @param QUI\Package\Package $Package
     */
    public static function onPackageSetup(QUI\Package\Package $Package)
    {
        if ($Package->getName() === 'quiqqer/cron') {
            self::checkCronTable();
        }
    }

    /**
     * Checks if the table cron is correct
     *
     * @return void
     */
    protected static function checkCronTable()
    {
        $categoryColumn = QUI::getDataBase()->table()->getColumn('cron', 'title');

        if ($categoryColumn['Type'] === 'varchar(1000)') {
            return;
        }

        $Stmnt = QUI::getDataBase()->getPDO()->prepare("ALTER TABLE cron MODIFY `title` VARCHAR(1000)");
        $Stmnt->execute();
    }

    /**
     * event : on admin header loaded
     */
    public static function onAdminLoad()
    {
        if (!defined('ADMIN')) {
            return;
        }

        if (!ADMIN) {
            return;
        }

        $User = QUI::getUserBySession();

        if (!$User->isSU()) {
            return;
        }

        $Package = QUI::getPackageManager()->getInstalledPackage('quiqqer/cron');
        $Config  = $Package->getConfig();

        // send admin info
        if (!$Config->get('settings', 'showAdminMessageIfCronNotRun')) {
            return;
        }

        // check last cron execution
        $CronManager = new Manager();
        $result      = $CronManager->getHistoryList(array(
            'page'    => 1,
            'perPage' => 1
        ));

        if (!isset($result[0])) {
            self::sendAdminInfoCronError();

            return;
        }

        $date = strtotime($result[0]['lastexec']);

        // in 24h no cron??
        if (time() - 86400 > $date) {
            self::sendAdminInfoCronError();
        }
    }

    /**
     * event : on admin loaded -> footer output
     */
    public static function adminLoadFooter()
    {
        $Package = QUI::getPackageManager()->getInstalledPackage('quiqqer/cron');
        $Config  = $Package->getConfig();

        // execute cron ?
        if ($Config->get('settings', 'executeOnAdminLogin')) {
            echo '
            <script>window.addEvent("load", function()
            {
                require(["Ajax"], function(QUIAjax)
                {
                    QUIAjax.post("package_quiqqer_cron_ajax_execute", function()
                    {

                    }, {
                        "package" : "quiqqer/cron"
                    });
                });
            });
            </script>';
        }
    }

    /**
     * send a message to the user, maybe an error in the crons exist
     * last 24h was no cron sended
     */
    public static function sendAdminInfoCronError()
    {
        QUI::getMessagesHandler()->sendAttention(
            QUI::getUserBySession(),
            QUI::getUserBySession()->getLocale()->get('quiqqer/cron', 'message.cron.admin.info.24h')
        );
    }


    /**
     * Event: onPackageInstall => Add default crons
     */
    public static function onPackageInstall(QUI\Package\Package $Package)
    {
        if ($Package->getName() !== 'quiqqer/cron') {
            return;
        }

        self::createDefaultCrons();
    }

    /**
     * Creates the default crons, if they do not exist yet
     *
     */
    public static function createDefaultCrons()
    {
        $CronManager = new Manager();

        $defaultCrons = array(
            // Clear temp folder
            "quiqqer/cron:0" => array(
                "min"   => "0",
                "hour"  => "0",
                "day"   => "*",
                "month" => "*",
                "dow"   => "*"
            ),
            // Clear sessions
            "quiqqer/cron:1" => array(
                "min"   => "0",
                "hour"  => "*",
                "day"   => "*",
                "month" => "*",
                "dow"   => "*"
            ),
            // Process mail queue
            "quiqqer/cron:6" => array(
                "min"   => "*/5",
                "hour"  => "*",
                "day"   => "*",
                "month" => "*",
                "dow"   => "*"
            ),
            // Calculate Media Folder Sizes
            "quiqqer/cron:7" => array(
                "min"   => "0",
                "hour"  => "3",
                "day"   => "*",
                "month" => "*",
                "dow"   => "*"
            ),
            // Calculate Package Folder Size
            "quiqqer/cron:8" => array(
                "min"   => "0",
                "hour"  => "3",
                "day"   => "*",
                "month" => "*",
                "dow"   => "*"
            ),
            // Calculate Cache Folder Size
            "quiqqer/cron:9" => array(
                "min"   => "0",
                "hour"  => "3",
                "day"   => "*",
                "month" => "*",
                "dow"   => "*"
            ),
            // Calculate Whole Installation Folder Size
            "quiqqer/cron:10" => array(
                "min"   => "0",
                "hour"  => "3",
                "day"   => "*",
                "month" => "*",
                "dow"   => "*"
            ),
            // Count All Files In Installation
            "quiqqer/cron:11" => array(
                "min"   => "0",
                "hour"  => "3",
                "day"   => "*",
                "month" => "*",
                "dow"   => "*"
            ),
            // Calculate VAR folder size
            "quiqqer/cron:12" => array(
                "min"   => "0",
                "hour"  => "3",
                "day"   => "*",
                "month" => "*",
                "dow"   => "*"
            )
        );

        // Parse the installed crons
        $installedCrons = array();
        foreach ($CronManager->getList() as $row) {
            $installedCrons[] = strtolower(trim($row['exec']));
        }


        // add the simple default crons, if they dont exist yet
        foreach ($defaultCrons as $identifier => $time) {
            $data = $CronManager->getCronData($identifier);

            $exec  = trim($data['exec']);
            $title = trim($data['title']);

            if (in_array(strtolower($exec), $installedCrons)) {
                continue;
            }

            $CronManager->add($title, $time['min'], $time['hour'], $time['day'], $time['month'], $time['dow']);
        }
    }

    /**
     * Event: onCreateProject => Add the publish cron for this project
     *
     * @param QUI\Projects\Project $Project
     */
    public static function onCreateProject(QUI\Projects\Project $Project)
    {
        $CronManager     = new Manager();
        $publishCronData = $CronManager->getCronData("quiqqer/cron:5");

        $languages      = $Project->getLanguages();
        $installedCrons = $CronManager->getList();

        foreach ($languages as $lang) {
            // Check that no cron with the same parameters exists yet
            foreach ($installedCrons as $installedCronData) {
                $installedParams  = json_decode($installedCronData['params'], true);
                $installedProject = "";
                $installedLang    = "";

                foreach ($installedParams as $param) {
                    if ($param['name'] == "project") {
                        $installedProject = mb_strtolower(trim($param['value']));
                    }

                    if ($param['name'] == "lang") {
                        $installedLang = mb_strtolower(trim($param['value']));
                    }
                }

                // Cron for this project & lang combination exists => skip
                if ($installedProject == mb_strtolower(trim($Project->getName())) &&
                    $installedLang == mb_strtolower(trim($lang))
                ) {
                    continue 2;
                }
            }

            # Prepare parameter array
            $params = array(
                array(
                    "name"  => "project",
                    "value" => $Project->getName()
                ),
                array(
                    "name"  => "lang",
                    "value" => $lang
                )
            );

            // Add the cron
            $CronManager->add($publishCronData['title'], "0", "*", "*", "*", "*", $params);
        }
    }
}
