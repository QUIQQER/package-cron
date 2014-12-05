<?php

/**
 * Return the Cronlist
 * @return array
 */
function package_quiqqer_cron_ajax_getList()
{
    $CronManager = new \QUI\Cron\Manager();
    return $CronManager->getList();
}

\QUI::$Ajax->register(
    'package_quiqqer_cron_ajax_getList',
    false,
    'Permission::checkAdminUser'
);
