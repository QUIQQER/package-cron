<?php

/**
 * Gets the current Status for this instance
 *
 * @return string - Returns the status
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_cron_ajax_cronservice_getStatus',
    function () {
        $CronService = new \QUI\Cron\CronService();

        return $CronService->getStatus();
    },
    false,
    'Permission::checkAdminUser'
);
