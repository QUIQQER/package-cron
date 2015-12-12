<?php

/**
 * Execute the cron list
 */

QUI::$Ajax->registerFunction(
    'package_quiqqer_cron_ajax_execute',
    function () {
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
