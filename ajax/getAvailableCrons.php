<?php

/**
 * Return the Cronlist
 * @return array
 */
function package_quiqqer_cron_ajax_getAvailableCrons()
{
    return (new \QUI\Cron\Manager())->getAvailableCrons();
}

\QUI::$Ajax->register(
    'package_quiqqer_cron_ajax_getAvailableCrons',
    false,
    'Permission::checkAdminUser'
);
