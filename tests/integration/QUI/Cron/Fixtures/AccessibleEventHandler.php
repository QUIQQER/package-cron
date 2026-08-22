<?php

namespace QUITests\Integration\Cron;

use QUI\Cron\EventHandler;

class AccessibleEventHandler extends EventHandler
{
    /**
     * @param array<int, array{name: string, value: string}> $params
     * @return array<int, array<int, array{name: string, value: string}>>
     */
    public static function getLanguageScopeParams(array $params): array
    {
        return parent::getCronsToCreateForLanguagesScope($params);
    }

    /**
     * @param array<int, array{name: string, value: string}> $params
     * @return array<int, array<int, array{name: string, value: string}>>
     */
    public static function getProjectScopeParams(array $params): array
    {
        return parent::getCronsToCreateForProjectsScope($params);
    }
}
