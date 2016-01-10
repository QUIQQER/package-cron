<?php

/**
 * Return the Cronlist
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_cron_ajax_getAvailableCrons',
    function () {
        $CronManager = new QUI\Cron\Manager();

        return $CronManager->getAvailableCrons();
    },
    false,
    'Permission::checkAdminUser'
);
