<?php

/**
 * Execute the cron list
 */

use QUI\Cron\Manager;

QUI::$Ajax->registerFunction(
    'package_quiqqer_cron_ajax_execute',
    function () {
        // only execute if quiqqer is completely set up
        if (Manager::isQuiqqerInstallerExecuted() === false) {
            return;
        }

        try {
            $Manager = new QUI\Cron\Manager();
            $Manager->execute();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError(
                'package_quiqqer_cron_ajax_execute() :: ' . $Exception->getMessage()
            );
        }

        QUI::getMessagesHandler()->clear();
    },
    false,
    'Permission::checkAdminUser'
);
