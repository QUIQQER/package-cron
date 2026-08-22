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
        $cronDefinitions = [];
        $Locale = QUI::getLocale();
        $Formatter = $Locale->getDateFormatter(
            IntlDateFormatter::SHORT,
            IntlDateFormatter::SHORT
        );

        foreach ($CronManager->getAvailableCrons() as $availableCron) {
            $exec = (string)($availableCron['exec'] ?? '');

            if ($exec === '') {
                continue;
            }

            $cronType = QUI\Cron\Manager::getCronType($availableCron);

            if (
                !isset($cronDefinitions[$exec])
                || $cronType === QUI\Cron\Manager::CRON_TYPE_SYSTEM
            ) {
                $cronDefinitions[$exec] = [
                    'type' => $cronType,
                    'description' => (string)($availableCron['description'] ?? '')
                ];
            }
        }

        foreach ($list as $key => $cron) {
            $cronDefinition = $cronDefinitions[$cron['exec']] ?? [];

            $list[$key]['cronType'] = $cronDefinition['type']
                ?? QUI\Cron\Manager::CRON_TYPE_CUSTOM;
            $list[$key]['desc'] = $cronDefinition['description'] ?? '';

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
