<?php

/**
 * activate a cron
 *
 * @param integer $cronId - Cron-ID
 *
 * @return array
 */

QUI::$Ajax->registerFunction(
    'package_quiqqer_cron_ajax_cron_get',
    function ($cronId) {
        $Manager = new QUI\Cron\Manager();

        return $Manager->getCronById($cronId);
    },
    ['cronId'],
    'Permission::checkAdminUser'
);
