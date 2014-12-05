<?php

/**
 * Return the Cronlist
 * @return array
 */
function package_quiqqer_cron_ajax_getAvailableCrons()
{
    $CronManager = new \QUI\Cron\Manager();
    return $CronManager->getAvailableCrons();
}

\QUI::$Ajax->register(
    'package_quiqqer_cron_ajax_getAvailableCrons',
    false,
    'Permission::checkAdminUser'
);
