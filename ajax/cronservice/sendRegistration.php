<?php

/**
 * Sends a registration to the cronservice server.
 *
 *
 */

QUI::$Ajax->registerFunction(
    'package_quiqqer_cron_ajax_cronservice_sendRegistration',
    function ($email) {
        $CronService = new \QUI\Cron\CronService();
        $CronService->register($email);
    },
    ['email'],
    'Permission::checkAdminUser'
);
