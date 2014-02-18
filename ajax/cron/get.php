<?php

/**
 * activate a cron
 * @param Integer $cronId - Cron-ID
 */
function package_quiqqer_cron_ajax_cron_get($cronId)
{
    $Manager = new \QUI\Cron\Manager();
    return $Manager->getCronById( $cronId );
}

\QUI::$Ajax->register(
    'package_quiqqer_cron_ajax_cron_get',
    array( 'cronId' ),
    'Permission::checkAdminUser'
);
