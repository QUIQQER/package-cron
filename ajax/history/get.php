<?php

/**
 * return the cron history
 */
function package_quiqqer_cron_ajax_history_get()
{
    return (new \QUI\Cron\Manager())->getHistoryList();
}

\QUI::$Ajax->register(
    'package_quiqqer_cron_ajax_history_get',
    false,
    'Permission::checkAdminUser'
);
