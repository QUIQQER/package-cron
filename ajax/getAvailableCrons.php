<?php

/**
 * Return the Cronlist
 */
function package_quiqqer_cron_ajax_getAvailableCrons()
{
    $Manager = new \QUI\Cron\Manager();

    return $Manager->getAvailableCrons();
}

\QUI::$Ajax->register(
    'package_quiqqer_cron_ajax_getAvailableCrons',
    false,
    'Permission::checkAdminUser'
);
