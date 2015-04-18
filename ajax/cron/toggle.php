<?php

/**
 * Toggle the status of a cron
 *
 * @param Integer $cronId
 *
 * @throws \QUI\Exception
 */
function package_quiqqer_cron_ajax_cron_toggle($cronId)
{
    $Manager = new \QUI\Cron\Manager();
    $data = $Manager->getCronById($cronId);

    if (!$data) {
        throw new \QUI\Exception('Cron not exists', 404);
    }

    if ($data['active'] == 1) {
        $Manager->deactivateCron($cronId);
    } else {
        $Manager->activateCron($cronId);
    }
}

\QUI::$Ajax->register(
    'package_quiqqer_cron_ajax_cron_toggle',
    array('cronId'),
    'Permission::checkAdminUser'
);
