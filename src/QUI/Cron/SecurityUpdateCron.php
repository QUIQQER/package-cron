<?php

/**
 * This File contains QUI\Cron\SecurityUpdateCron
 */

namespace QUI\Cron;

use QUI;

/**
 * @author www.pcsg.de (Henning Leutz)
 */
class SecurityUpdateCron
{
    public static function execute($params, $CronManager)
    {
        $Console = new QUI\System\Console\Tools\SecurityUpdate();

        if (!empty($params['email'])) {
            $Console->setArgument('email', '');
        }

        $Console->execute();
    }
}
