<?php

/**
 * This file contains QUI\Cron\Events
 */

namespace QUI\Cron;

use QUI;
use QUI\Exception;

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
     * @return void
     * @throws Exception
     */
    public static function updateEnd()
    {
        QUI\Cron\Update::clearUpdateCheck();
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

        try {
            $Package = QUI::getPackageManager()->getInstalledPackage('quiqqer/cron');
            $Config = $Package->getConfig();
        } catch (QUI\Exception $Exception) {
            return;
        }


        // send admin info
        if (!$Config->get('settings', 'showAdminMessageIfCronNotRun')) {
            return;
        }

        // check last cron execution
        $CronManager = new Manager();
        $result = $CronManager->getHistoryList([
            'page'    => 1,
            'perPage' => 1
        ]);

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
        try {
            $Package = QUI::getPackageManager()->getInstalledPackage('quiqqer/cron');
            $Config = $Package->getConfig();
        } catch (QUI\Exception $Exception) {
            return;
        }

        echo '
            <script>
            window.addEvent("load", function() {
                require(["package/quiqqer/cron/bin/UpdateInfo"]);
            });
            </script>
        ';

        // execute cron ?
        if ($Config->get('settings', 'executeOnAdminLogin')) {
            echo '
            <script>
            window.addEvent("load", function()
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
     *
     * @param QUI\Package\Package $Package
     */
    public static function onPackageInstall(QUI\Package\Package $Package)
    {
        if ($Package->getName() !== 'quiqqer/cron') {
            return;
        }

        self::createDefaultCrons();
    }

    /**
     * Event: onQuiqqerInstallFinish => Add default crons
     */
    public static function onQuiqqerInstallFinish()
    {
        self::createDefaultCrons();
    }

    /**
     * Creates the default crons, if they do not exist yet
     *
     */
    public static function createDefaultCrons()
    {
        $CronManager = new Manager();

        $defaultCrons = [
            // Clear temp folder
            "quiqqer/cron:0"         => [
                "min"   => "0",
                "hour"  => "0",
                "day"   => "*",
                "month" => "*",
                "dow"   => "*",
                "exec"  => '\QUI\Cron\QuiqqerCrons::clearTempFolder'
            ],

            // Clear sessions
            "quiqqer/cron:1"         => [
                "min"   => "0",
                "hour"  => "*",
                "day"   => "*",
                "month" => "*",
                "dow"   => "*",
                'exec'  => '\QUI\Cron\QuiqqerCrons::clearSessions'
            ],

            // Process mail queue
            "quiqqer/cron:6"         => [
                "min"   => "*/5",
                "hour"  => "*",
                "day"   => "*",
                "month" => "*",
                "dow"   => "*",
                "exec"  => '\QUI\Cron\QuiqqerCrons::mailQueue'
            ],

            // Calculate Media Folder Sizes
            "quiqqer/cron:7"         => [
                "min"   => "0",
                "hour"  => "3",
                "day"   => "*",
                "month" => "*",
                "dow"   => "*",
                'exec'  => '\QUI\Cron\QuiqqerCrons::calculateMediaFolderSizes'
            ],

            // Calculate Package Folder Size
            "quiqqer/cron:8"         => [
                "min"   => "0",
                "hour"  => "3",
                "day"   => "*",
                "month" => "*",
                "dow"   => "*",
                'exec'  => '\QUI\Cron\QuiqqerCrons::calculatePackageFolderSize'
            ],

            // Calculate Cache Folder Size
            "quiqqer/cron:9"         => [
                "min"   => "0",
                "hour"  => "3",
                "day"   => "*",
                "month" => "*",
                "dow"   => "*",
                'exec'  => '\QUI\Cron\QuiqqerCrons::calculateCacheFolderSize'
            ],

            // Calculate Whole Installation Folder Size
            "quiqqer/cron:10"        => [
                "min"   => "0",
                "hour"  => "3",
                "day"   => "*",
                "month" => "*",
                "dow"   => "*",
                'exec'  => '\QUI\Cron\QuiqqerCrons::calculateWholeInstallationFolderSize'
            ],

            // Count All Files In Installation
            "quiqqer/cron:11"        => [
                "min"   => "0",
                "hour"  => "3",
                "day"   => "*",
                "month" => "*",
                "dow"   => "*",
                'exec'  => '\QUI\Cron\QuiqqerCrons::countAllFilesInInstallation'
            ],

            // Calculate VAR folder size
            "quiqqer/cron:12"        => [
                "min"   => "0",
                "hour"  => "3",
                "day"   => "*",
                "month" => "*",
                "dow"   => "*",
                'exec'  => '\QUI\Cron\QuiqqerCrons::calculateVarFolderSize'
            ],

            // Calculate VAR folder size
            "quiqqer/cron:13"        => [
                "min"   => "0",
                "hour"  => "1",
                "day"   => "*",
                "month" => "*",
                "dow"   => "*",
                'exec'  => '\QUI\Cron\Update::check'
            ],

            // Login-Logger purge logs (as decided with mor & hen)
            "quiqqer/login-logger:0" => [
                "min"   => "0",
                "hour"  => "3",
                "day"   => "*",
                "month" => "*",
                "dow"   => "*",
                'exec'  => '\QUI\LoginLogger\Cron::purgeLog'
            ]
        ];

        // Parse the installed crons
        $installedCrons = [];

        foreach ($CronManager->getList() as $row) {
            $installedCrons[] = strtolower(trim($row['exec']));
        }

        $available = $CronManager->getAvailableCrons();
        $getCronData = function ($exec) use ($available) {
            foreach ($available as $data) {
                if ($data['exec'] === $exec) {
                    return $data;
                }
            }

            return false;
        };


        // add the simple default crons, if they dont exist yet
        foreach ($defaultCrons as $identifier => $time) {
            $exec = \trim($time['exec']);
            $data = $getCronData($exec);
            $title = \trim($data['title']);

            if (\in_array(\strtolower($exec), $installedCrons)) {
                continue;
            }

            try {
                $CronManager->add($title, $time['min'], $time['hour'], $time['day'], $time['month'], $time['dow'], [
                    'exec' => $exec
                ]);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }
    }

    /**
     * Event: onCreateProject => Add the publish cron for this project
     *
     * @param QUI\Projects\Project $Project
     */
    public static function onCreateProject(QUI\Projects\Project $Project)
    {
        $CronManager = new Manager();
        $publishCronData = $CronManager->getCronData("quiqqer/cron:5");

        $languages = $Project->getLanguages();
        $installedCrons = $CronManager->getList();

        foreach ($languages as $lang) {
            // Check that no cron with the same parameters exists yet
            foreach ($installedCrons as $installedCronData) {
                $installedParams = json_decode($installedCronData['params'], true);
                $installedProject = "";
                $installedLang = "";

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
            $params = [
                [
                    "name"  => "project",
                    "value" => $Project->getName()
                ],
                [
                    "name"  => "lang",
                    "value" => $lang
                ]
            ];

            try {
                // Add the cron
                $CronManager->add($publishCronData['title'], "0", "*", "*", "*", "*", $params);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }
    }
}
