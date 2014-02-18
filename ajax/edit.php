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
function package_quiqqer_cron_ajax_edit($cronId, $cron, $min, $hour, $day, $month)
{
    $Manager = new \QUI\Cron\Manager();
    $Manager->edit( $cronId, $cron, $min, $hour, $day, $month );
}

\QUI::$Ajax->register(
    'package_quiqqer_cron_ajax_add',
    array( 'cronId', 'cron', 'min', 'hour', 'day', 'month' ),
    'Permission::checkAdminUser'
);
