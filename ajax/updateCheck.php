<?php

/**
 * Return the available updates
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_cron_ajax_updateCheck',
    function () {
        return QUI\Cron\Update::getAvailableUpdates();
    },
    false,
    'Permission::checkAdminUser'
);
