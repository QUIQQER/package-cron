<?php

/**
 * Add a cron to the cron list
 *
 * @param {String} $cron
 * @param {String} $min
 * @param {String} $hour
 * @param {String} $day
 * @param {String} $month
 */
function package_quiqqer_cron_ajax_delete($ids)
{
    $ids     = json_decode( $ids, true );
    $Manager = new \QUI\Cron\Manager();

    $Manager->deleteCronIds( $ids );
}

\QUI::$Ajax->register(
    'package_quiqqer_cron_ajax_delete',
    array( 'ids' ),
    'Permission::checkAdminUser'
);
