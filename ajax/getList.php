<?php

/**
 * Return the Cronlist
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_cron_ajax_getList',
    function () {
        $CronManager = new QUI\Cron\Manager();
        $list        = $CronManager->getList();
        $Locale      = QUI::getLocale();

        foreach ($list as $key => $cron) {
            if ($Locale->isLocaleString($cron['title'])) {
                $locale              = $Locale->getPartsOfLocaleString($cron['title']);
                $list[$key]['title'] = $Locale->get($locale[0], $locale[1]);
            }
        }

        return $list;
    },
    false,
    'Permission::checkAdminUser'
);
