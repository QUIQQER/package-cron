<?php

/**
 * deactivate a cron
 *
 * @param integer $cronId - Cron-ID
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_cron_ajax_cron_deactivate',
    function ($cronId) {
        $Manager = new QUI\Cron\Manager();
        $Manager->deactivateCron($cronId);
    },
    array('cronId'),
    'Permission::checkAdminUser'
);
