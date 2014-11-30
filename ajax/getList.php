<?php

/**
 * Return the Cronlist
 * @return array
 */
function package_quiqqer_cron_ajax_getList()
{
    return (new \QUI\Cron\Manager())->getList();
}

\QUI::$Ajax->register(
    'package_quiqqer_cron_ajax_getList',
    false,
    'Permission::checkAdminUser'
);
