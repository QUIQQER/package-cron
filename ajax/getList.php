<?php

/**
 * Return the Cronlist
 */
function package_quiqqer_cron_ajax_getList()
{
    $Manager = new \QUI\Cron\Manager();

    return $Manager->getList();
}

\QUI::$Ajax->register(
    'package_quiqqer_cron_ajax_getList',
    false,
    'Permission::checkAdminUser'
);
