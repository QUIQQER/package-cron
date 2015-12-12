<?php

/**
 * Delete a cron to the cron list
 *
 * @param string $ids - json array
 */
function package_quiqqer_cron_ajax_delete($ids)
{
    $ids = json_decode($ids, true);
    $Manager = new QUI\Cron\Manager();

    $Manager->deleteCronIds($ids);
}

QUI::$Ajax->register(
    'package_quiqqer_cron_ajax_delete',
    array('ids'),
    'Permission::checkAdminUser'
);
