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
            'page' => 1,
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
            QUI::getLocale()->get('quiqqer/cron', 'message.cron.admin.info.24h')
        );
    }
}
