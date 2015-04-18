<?php

/**
 * deactivate a cron
 *
 * @param Integer $cronId - Cron-ID
 */
function package_quiqqer_cron_ajax_cron_executeCron($cronId)
{
    $Manager = new \QUI\Cron\Manager();
    $Manager->executeCron($cronId);
}

\QUI::$Ajax->register(
    'package_quiqqer_cron_ajax_cron_executeCron',
    array('cronId'),
    'Permission::checkAdminUser'
);
