<?php

/**
 * return the cron history
 */
function package_quiqqer_cron_ajax_history_get()
{
    $CronManager = new \QUI\Cron\Manager();
    return $CronManager->getHistoryList();
}

\QUI::$Ajax->register(
    'package_quiqqer_cron_ajax_history_get',
    false,
    'Permission::checkAdminUser'
);
