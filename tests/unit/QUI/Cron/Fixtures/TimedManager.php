<?php

namespace QUITests\Unit\Cron\Fixtures;

use DateTimeImmutable;
use QUI\Cron\Manager;

class TimedManager extends Manager
{
    public function __construct(private readonly DateTimeImmutable $currentTime)
    {
    }

    protected function getCurrentDateTime(): DateTimeImmutable
    {
        return $this->currentTime;
    }

    /**
     * @param array<string, mixed> $entry
     */
    public function isCronDue(array $entry, DateTimeImmutable $lastExecutionDate): bool
    {
        return $this->shouldExecuteCron($entry, $lastExecutionDate);
    }
}
