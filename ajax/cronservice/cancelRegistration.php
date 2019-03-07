<?php

/**
 * Requests the server to cancel the registration
 *
 * @param - The email which was used for registration.
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_cron_ajax_cronservice_cancelRegistration',
    function () {
        $CronService = new \QUI\Cron\CronService();
        $CronService->cancelRegistration();
    },
    false,
    'Permission::checkAdminUser'
);
