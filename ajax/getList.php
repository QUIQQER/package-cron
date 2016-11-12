<?php

/**
 * Return the Cronlist
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_cron_ajax_getList',
    function () {
        $CronManager = new QUI\Cron\Manager();
        return $CronManager->getList();
    },
    false,
    'Permission::checkAdminUser'
);
