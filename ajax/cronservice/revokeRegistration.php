<?php

/**
 * Revokes a registration on the cronservice server.
 *
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_cron_ajax_cronservice_revokeRegistration',
    function () {
        $CronService = new \QUI\Cron\CronService();
        $CronService->revokeRegistration();
    },
    [],
    ''
);
