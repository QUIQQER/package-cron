<?php

/**
 * return the cron history
 */
function package_quiqqer_cron_ajax_history_get($params)
{
    $CronManager = new \QUI\Cron\Manager();
    $params = json_decode($params, true);

    return array(
        'page'  => (int)$params['page'],
        'data'  => $CronManager->getHistoryList($params),
        'total' => $CronManager->getHistoryCount()
    );
}

\QUI::$Ajax->register(
    'package_quiqqer_cron_ajax_history_get',
    array('params'),
    'Permission::checkAdminUser'
);
