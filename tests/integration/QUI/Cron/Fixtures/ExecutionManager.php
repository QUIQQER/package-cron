<?php

namespace QUITests\Integration\Cron\Fixtures;

use DateTimeInterface;
use QUI\Cron\Manager;

class ExecutionManager extends Manager
{
    /** @var array<int, array<string, mixed>> */
    public array $receivedEntries = [];

    public int $getListCalls = 0;

    /**
     * @param array<int, array<string, mixed>> $entries
     */
    public function __construct(
        private readonly array $entries,
        private readonly bool $updateRunning = false
    ) {
    }

    public function getList(): array
    {
        $this->getListCalls++;

        return $this->entries;
    }

    protected function isSystemUpdateRunning(): bool
    {
        return $this->updateRunning;
    }

    protected function executeCronList(array $activeList, DateTimeInterface $EndTime): void
    {
        $this->receivedEntries = $activeList;
    }
}
