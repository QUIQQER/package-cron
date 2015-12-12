<?php

/**
 * activate a cron
 *
 * @param Integer $cronId - Cron-ID
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_cron_ajax_cron_activate',
    function ($cronId) {
        $Manager = new QUI\Cron\Manager();
        $Manager->activateCron($cronId);
    },
    array('cronId'),
    'Permission::checkAdminUser'
);
