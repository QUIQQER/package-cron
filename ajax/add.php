<?php

/**
 * Add a cron to the cron list
 *
 * @param String $cron
 * @param String $min
 * @param String $hour
 * @param String $day
 * @param String $month
 * @param String $dayOfWeek
 * @param string $params
 */
function package_quiqqer_cron_ajax_add(
    $cron,
    $min,
    $hour,
    $day,
    $month,
    $dayOfWeek,
    $params
) {
    $params = json_decode($params, true);

    $Manager = new QUI\Cron\Manager();
    $Manager->add($cron, $min, $hour, $day, $month, $dayOfWeek, $params);
}

QUI::$Ajax->register(
    'package_quiqqer_cron_ajax_add',
    array('cron', 'min', 'hour', 'day', 'month', 'dayOfWeek', 'params'),
    'Permission::checkAdminUser'
);
