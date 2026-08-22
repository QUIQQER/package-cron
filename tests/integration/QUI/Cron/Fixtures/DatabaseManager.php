<?php

namespace QUITests\Integration\Cron\Fixtures;

use QUI\Cron\Manager;

class DatabaseManager extends Manager
{
    /**
     * @param array<int, array<string, mixed>> $availableCrons
     */
    public function __construct(private readonly array $availableCrons)
    {
    }

    public function getAvailableCrons(): array
    {
        return $this->availableCrons;
    }
}
