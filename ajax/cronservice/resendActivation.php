<?php

/**
 * Requests the server to resend the activationmail again
 *
 * @param - The email which was used for registration.
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_cron_ajax_cronservice_resendActivation',
    function () {
        $CronService = new \QUI\Cron\CronService();
        $CronService->resendActivationMail();
    },
    array(),
    ''
);
