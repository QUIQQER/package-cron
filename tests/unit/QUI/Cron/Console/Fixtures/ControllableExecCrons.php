<?php

namespace QUITests\Unit\Cron\Console\Fixtures;

use QUI\Cron\Console\ExecCrons;
use RuntimeException;

class ControllableExecCrons extends ExecCrons
{
    public int $unlockCalls = 0;
    public int $runCalls = 0;
    public int $listCalls = 0;
    public int $listAllCalls = 0;
    public bool $throwOnRead = false;
    public bool $stopAfterUnlock = false;

    /** @var array<int, int> */
    public array $runCronCalls = [];

    /** @var array<int, string> */
    public array $inputs = [];

    /** @var array<int, string> */
    public array $output = [];

    public function run(): void
    {
        $this->runCalls++;
    }

    public function listCrons(): void
    {
        $this->listCalls++;
    }

    public function listAllCrons(): void
    {
        $this->listAllCalls++;
    }

    public function runCron(bool | int $cronId = false): void
    {
        $this->runCronCalls[] = (int)$cronId;
    }

    public function unlock(): void
    {
        $this->unlockCalls++;

        if ($this->stopAfterUnlock) {
            throw new RuntimeException('stop-unlock');
        }
    }

    public function write(string $msg = '', bool | string $color = false, bool | string $bg = false): void
    {
        $this->output[] = $msg;
    }

    public function writeLn(string $msg = '', bool | string $color = false, bool | string $bg = false): void
    {
        $this->output[] = $msg;
    }

    public function readInput(): string
    {
        if ($this->throwOnRead) {
            throw new RuntimeException('stop-read');
        }

        if (!count($this->inputs)) {
            throw new RuntimeException('missing-input');
        }

        return array_shift($this->inputs);
    }

    public function resetColor(): void
    {
    }
}
