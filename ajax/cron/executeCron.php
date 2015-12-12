<?php

/**
 * deactivate a cron
 *
 * @param Integer $cronId - Cron-ID
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_cron_ajax_cron_executeCron',
    function ($cronId) {
        $Manager = new QUI\Cron\Manager();
        $Manager->executeCron($cronId);
    },
    array('cronId'),
    'Permission::checkAdminUser'
);
