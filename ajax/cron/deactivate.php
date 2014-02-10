<?php

/**
 * deactivate a cron
 * @param Integer $cronId - Cron-ID
 */
function package_quiqqer_cron_ajax_cron_deactivate($cronId)
{
    $Manager = new \QUI\Cron\Manager();
    $Manager->deactivateCron( $cronId );
}

\QUI::$Ajax->register(
    'package_quiqqer_cron_ajax_cron_deactivate',
    array( 'cronId' ),
    'Permission::checkAdminUser'
);
