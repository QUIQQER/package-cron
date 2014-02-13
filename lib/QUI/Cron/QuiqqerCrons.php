<?php

/**
 * This File contains QUI\Cron\Crons
 */

namespace QUI\Cron;

/**
 * Cron Manager
 *
 * @author www.pcsg.de (Henning Leutz)
 */

class QuiqqerCrons
{
    /**
     * Clear the temp folder
     */
    static function clearTempFolder()
    {
        $Temp = \QUI::getTemp();
        $Temp->clear();
    }
}