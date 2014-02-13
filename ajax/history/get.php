<?php

/**
 * activate a cron
 * @param Integer $cronId - Cron-ID
 */
function package_quiqqer_cron_ajax_history_get()
{
    $Manager = new \QUI\Cron\Manager();

    return $Manager->getHistoryList();
}

\QUI::$Ajax->register(
    'package_quiqqer_cron_ajax_history_get',
    false,
    'Permission::checkAdminUser'
);
