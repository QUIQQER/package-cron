<?php

/**
 * Return the cron list
 *
 * @return array
 */

QUI::getAjax()->registerFunction(
    'package_quiqqer_cron_ajax_getList',
    function () {
        $CronManager = new QUI\Cron\Manager();
        $list = $CronManager->getList();
        $Locale = QUI::getLocale();
        $Formatter = $Locale->getDateFormatter(
            IntlDateFormatter::SHORT,
            IntlDateFormatter::SHORT
        );

        foreach ($list as $key => $cron) {
            [$localeGroup, $localeVar] = $Locale->getPartsOfLocaleString($cron['title']);

            if ($localeGroup !== null && $localeVar !== null) {
                $list[$key]['title'] = $Locale->get($localeGroup, $localeVar);
            }

            if (!empty($list[$key]['lastexec'])) {
                $list[$key]['lastexec'] = $Formatter->format(strtotime($list[$key]['lastexec']));
            } else {
                $list[$key]['lastexec'] = '';
            }
        }

        return $list;
    },
    false,
    'Permission::checkAdminUser'
);
