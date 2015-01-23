<?php

/**
 * Execute the cron list
 */
function package_quiqqer_cron_ajax_execute()
{
    try
    {
        $Manager = new \QUI\Cron\Manager();
        $Manager->execute();

    } catch ( QUI\Exception $Exception )
    {
        \QUI\System\Log::addError(
            'package_quiqqer_cron_ajax_execute() :: ' . $Exception->getMessage()
        );
    }

    QUI::getMessagesHandler()->clear();
}

\QUI::$Ajax->register(
    'package_quiqqer_cron_ajax_execute',
    false,
    'Permission::checkAdminUser'
);
