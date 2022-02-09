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
            $Config  = $Package->getConfig();
        } catch (QUI\Exception $Exception) {
            return;
        }


        // send admin info
        if (!$Config->get('settings', 'showAdminMessageIfCronNotRun')) {
            return;
        }

        // check last cron execution
        $CronManager = new Manager();
        $result      = $CronManager->getHistoryList([
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
            $Config  = $Package->getConfig();
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
        self::createAutoCreateCrons();
    }

    /**
     * Event: onCreateProject => Add the publish cron for this project
     *
     * @param QUI\Projects\Project $Project
     */
    public static function onCreateProject(QUI\Projects\Project $Project)
    {
        self::createAutoCreateCrons(Manager::AUTOCREATE_SCOPE_PROJECTS);
    }

    /**
     * Create all crons with a <autocreate> items.
     *
     * @param string|null $scope (optional) - Only create crons for given scope (see Manager::AUTOCREATE_SCOPE_*)
     * @return void
     */
    protected static function createAutoCreateCrons(?string $scope = null): void
    {
        $CronManager = new Manager();

        foreach ($CronManager->getAvailableCrons() as $cron) {
            $title = $cron['title'];
            $exec  = $cron['exec'];

            foreach ($cron['autocreate'] as $autocreate) {
                // Check if cron already exists
                $params = $autocreate['params'];
                [$min, $hour, $day, $month, $dayOfWeek] = \explode(' ', $autocreate['interval']);

                // Parse params by scope and placeholders
                if ($scope && $scope !== $autocreate['scope']) {
                    continue;
                }

                switch ($autocreate['scope']) {
                    case Manager::AUTOCREATE_SCOPE_PROJECTS:
                        $createWithParams = self::getCronsToCreateForProjectsScope($params);
                        break;

                    default:
                        $createWithParams = [$params];
                }

                // Create crons
                foreach ($createWithParams as $createParams) {
                    try {
                        if ($CronManager->cronWithExecAndParamsExists($exec, $createParams)) {
                            continue;
                        }
                    } catch (\Exception $Exception) {
                        QUI\System\Log::writeException($Exception);
                        continue;
                    }

                    $createParams['exec'] = $exec;

                    try {
                        $CronManager->add($title, $min, $hour, $day, $month, $dayOfWeek, $createParams);

                        $cronId = QUI::getDataBase()->getPDO()->lastInsertId('id');

                        if (!$autocreate['active']) {
                            QUI::getDataBase()->update(
                                $CronManager::table(),
                                [
                                    'active' => 0
                                ],
                                [
                                    'id' => $cronId
                                ]
                            );
                        }
                    } catch (\Exception $Exception) {
                        QUI\System\Log::writeException($Exception);
                    }
                }
            }
        }
    }

    /**
     * Get all crons to create for autocreate scope "projects".
     *
     * @param array $params
     * @return array
     */
    protected static function getCronsToCreateForProjectsScope(array $params): array
    {
        $createCrons = [];

        try {
            $projects = QUI::getProjectManager()::getProjects(true);
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            return $createCrons;
        }

        /** @var QUI\Projects\Project $Project */
        foreach ($projects as $Project) {
            $projectName = $Project->getName();

            foreach ($Project->getLanguages() as $language) {
                $projectCronParams = $params;

                foreach ($projectCronParams as $k => $v) {
                    $projectCronParams[$k] = \str_replace(
                        [
                            '[projectName]',
                            '[projectLang]'
                        ],
                        [
                            $projectName,
                            $language
                        ],
                        $v
                    );
                }

                $createCrons[] = $projectCronParams;
            }
        }

        return $createCrons;
    }
}
