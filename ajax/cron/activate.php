<?php

/**
 * activate a cron
 * @param Integer $cronId - Cron-ID
 */
function package_quiqqer_cron_ajax_cron_activate($cronId)
{
    $Manager = new \QUI\Cron\Manager();
    $Manager->activateCron( $cronId );
}

\QUI::$Ajax->register(
    'package_quiqqer_cron_ajax_cron_activate',
    array( 'cronId' ),
    'Permission::checkAdminUser'
);
