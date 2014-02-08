<?php

/**
 * This File contains QUI\Cron\Manager
 */

namespace QUI\Cron;

/**
 * Cron Manager
 *
 * @author www.pcsg.de (Henning Leutz)
 */

class Manager
{
    /**
     * Return the cron tabe
     *
     * @return String
     */
    static function Table()
    {
        return QUI_DB_PRFX .'cron';
    }


    /**
     * Print a message to the log cron.log
     *
     * @param String $message - Message
     */
    static function log($message)
    {
        $User = \QUI::getUsers()->getUserBySession();

        $dir  = VAR_DIR . 'log/';
        $file = $dir . 'cron_'. date('Y-m-d') .'.log';

        $str = '['. date('Y-m-d H:i:s') .' :: '. $User->getName() .'] '. $message;

        QUI\System\Log::write( $str, 'cron' );
    }
}