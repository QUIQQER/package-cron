<?php

/**
 * This file contains QUI\Cron\Events
 */

namespace QUI\Cron;

use QUI;

/**
 * Cron Main Events
 *
 * @author www.namerobot.com (Henning Leutz)
 */

class Events
{
    /**
     * event : on admin header loaded
     */
    static function onAdminLoaded()
    {
        if ( !defined( 'ADMIN' ) ) {
            return;
        }

        if ( !ADMIN ) {
            return;
        }

        $User = QUI::getUserBySession();

        if ( !$User->isSU() ) {
            return;
        }

        // check last cron execution
        $CronManager = new Manager();
        $result      = $CronManager->getHistoryList(array(
            'page'    => 1,
            'perPage' => 1
        ));

        if ( !isset( $result[ 0 ] ) )
        {
            self::sendAdminInfoCronError();
            return;
        }

        $date = strtotime( $result[ 0 ][ 'lastexec' ] );

        // in 24h no cron??
        if ( time() - 86400 > $date ) {
            self::sendAdminInfoCronError();
        }
    }

    /**
     * send a message to the user, maybe an error in the crons exist
     * last 24h was no cron sended
     */
    static function sendAdminInfoCronError()
    {
        QUI::getMessagesHandler()->sendAttention(
            QUI::getUserBySession(),
            QUI::getLocale()->get( 'quiqqer/cron', 'message.cron.admin.info.24h' )
        );
    }
}
