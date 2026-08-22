<?php

namespace QUITests\Unit\Cron\Fixtures;

use QUI\Cron\Manager;

class AccessibleManager extends Manager
{
    /** @var array<int, array<string, mixed>> */
    private array $availableCrons;

    /** @var array<int, array<string, mixed>> */
    private array $storedCrons;

    public function __construct(
        private readonly bool $cliExecution = true,
        array $availableCrons = [],
        array $storedCrons = []
    ) {
        $this->availableCrons = $availableCrons;
        $this->storedCrons = $storedCrons;
    }

    public function getAvailableCrons(): array
    {
        return $this->availableCrons;
    }

    public function getList(): array
    {
        return $this->storedCrons;
    }

    /**
     * @param array<string, mixed> $cron
     */
    public function canExecute(array $cron): bool
    {
        return $this->canExecuteCron($cron);
    }

    public function cronExistsForTest(string $cron): bool
    {
        return $this->cronExists($cron);
    }

    /**
     * @param array<string, mixed> $entry
     */
    public function getExpression(array $entry): string
    {
        return $this->getCronExpression($entry);
    }

    public function mustStop(): bool
    {
        return $this->shouldStopExecution();
    }

    public function readSystemUpdateState(): bool
    {
        return parent::isSystemUpdateRunning();
    }

    public function readCurrentDateTime(): \DateTimeImmutable
    {
        return parent::getCurrentDateTime();
    }

    protected function isCliExecution(): bool
    {
        return $this->cliExecution;
    }

    protected function isSystemUpdateRunning(): bool
    {
        return false;
    }
}
