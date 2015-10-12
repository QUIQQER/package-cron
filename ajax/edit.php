<?php

/**
 * Add a cron to the cron list
 *
 * @param String $cronId
 * @param String $cron
 * @param String $min
 * @param String $hour
 * @param String $day
 * @param String $month
 * @param String $params
 */
function package_quiqqer_cron_ajax_edit(
    $cronId,
    $cron,
    $min,
    $hour,
    $day,
    $month,
    $params
) {
    $params = json_decode($params, true);

    $Manager = new \QUI\Cron\Manager();
    $Manager->edit($cronId, $cron, $min, $hour, $day, $month, $params);
}

\QUI::$Ajax->register(
    'package_quiqqer_cron_ajax_edit',
    array('cronId', 'cron', 'min', 'hour', 'day', 'month', 'params'),
    'Permission::checkAdminUser'
);
