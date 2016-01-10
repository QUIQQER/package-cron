<?php

/**
 * return the cron history
 *
 * @param {array} $params - filter params
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_cron_ajax_history_get',
    function ($params) {
        $CronManager = new QUI\Cron\Manager();
        $params      = json_decode($params, true);

        return array(
            'page' => (int)$params['page'],
            'data' => $CronManager->getHistoryList($params),
            'total' => $CronManager->getHistoryCount()
        );
    },
    array('params'),
    'Permission::checkAdminUser'
);
