<?php

namespace QUITests\Integration\Cron\Fixtures;

use QUI\Cron\Manager;

class ExecutableCron
{
    /** @var array<int, array<string, mixed>> */
    public static array $calls = [];

    /**
     * @param array<string, mixed> $params
     */
    public static function execute(array $params, Manager $Manager): void
    {
        self::$calls[] = [
            'params' => $params,
            'manager' => $Manager
        ];
    }
}
