<?php

namespace QUITests\Integration\Cron\Console\Fixtures;

use QUI\Cron\Console\ExecCrons;

class OutputExecCrons extends ExecCrons
{
    /** @var array<int, string> */
    public array $output = [];

    public function write(string $msg = '', bool | string $color = false, bool | string $bg = false): void
    {
        $this->output[] = $msg;
    }

    public function writeLn(string $msg = '', bool | string $color = false, bool | string $bg = false): void
    {
        $this->output[] = $msg;
    }

    public function resetColor(): void
    {
    }
}
