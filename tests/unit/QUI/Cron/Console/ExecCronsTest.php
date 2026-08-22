<?php

namespace QUITests\Unit\Cron\Console;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use QUITests\Unit\Cron\Console\Fixtures\ControllableExecCrons;

require_once __DIR__ . '/Fixtures/ControllableExecCrons.php';

class ExecCronsTest extends TestCase
{
    private function createTool(): ControllableExecCrons
    {
        return new ControllableExecCrons();
    }

    public function testExecuteWithUnlockArgumentCallsUnlock(): void
    {
        $Tool = $this->createTool();
        $Tool->setArgument('--unlock', true);

        $Tool->execute();

        $this->assertSame(1, $Tool->unlockCalls);
        $this->assertSame(0, $Tool->runCalls);
        $this->assertSame(0, $Tool->listCalls);
        $this->assertSame(0, $Tool->listAllCalls);
    }

    public function testCommandReadListsUnlockCommand(): void
    {
        $Tool = $this->createTool();
        $Tool->throwOnRead = true;

        try {
            $Tool->commandRead();
            $this->fail('Expected commandRead to stop after readInput().');
        } catch (RuntimeException $Exception) {
            $this->assertSame('stop-read', $Exception->getMessage());
        }

        $this->assertStringContainsString(
            'unlock',
            implode("\n", $Tool->output)
        );
        $this->assertStringContainsString(
            'cron execution lock',
            implode("\n", $Tool->output)
        );
    }

    public function testCommandReadUnlockDispatchesUnlock(): void
    {
        $Tool = $this->createTool();
        $Tool->inputs = ['unlock'];
        $Tool->stopAfterUnlock = true;

        try {
            $Tool->commandRead();
            $this->fail('Expected commandRead to stop after unlock().');
        } catch (RuntimeException $Exception) {
            $this->assertContains(
                $Exception->getMessage(),
                ['stop-unlock', 'missing-input']
            );
        }

        $this->assertSame(1, $Tool->unlockCalls);
    }

    public function testExecuteDispatchesRunListListAllAndSpecificCron(): void
    {
        $RunTool = $this->createTool();
        $RunTool->setArgument('--run', true);
        $RunTool->execute();

        $ListTool = $this->createTool();
        $ListTool->setArgument('--list', true);
        $ListTool->execute();

        $ListAllTool = $this->createTool();
        $ListAllTool->setArgument('--list-all', true);
        $ListAllTool->execute();

        $CronTool = $this->createTool();
        $CronTool->setArgument('--cron', 42);
        $CronTool->execute();

        $this->assertSame(1, $RunTool->runCalls);
        $this->assertSame(1, $ListTool->listCalls);
        $this->assertSame(1, $ListAllTool->listAllCalls);
        $this->assertSame([42], $CronTool->runCronCalls);
    }

    public function testExecuteWithoutArgumentsShowsInteractivePrompt(): void
    {
        $Tool = $this->createTool();
        $Tool->throwOnRead = true;

        try {
            $Tool->execute();
            $this->fail('Expected interactive input to stop the test tool.');
        } catch (RuntimeException $Exception) {
            $this->assertSame('stop-read', $Exception->getMessage());
        }

        $this->assertContains('Welcome to the Cron Manager', $Tool->output);
    }

    public function testCommandReadDispatchesInteractiveCommands(): void
    {
        $RunTool = $this->createTool();
        $RunTool->inputs = ['run'];
        $this->runUntilInputIsExhausted($RunTool);

        $ListTool = $this->createTool();
        $ListTool->inputs = ['list'];
        $this->runUntilInputIsExhausted($ListTool);

        $ListAllTool = $this->createTool();
        $ListAllTool->inputs = ['list-all'];
        $this->runUntilInputIsExhausted($ListAllTool);

        $CronTool = $this->createTool();
        $CronTool->inputs = ['cron', '23'];
        $this->runUntilInputIsExhausted($CronTool);

        $UnknownTool = $this->createTool();
        $UnknownTool->inputs = ['unknown'];
        $this->runUntilInputIsExhausted($UnknownTool);

        $this->assertSame(1, $RunTool->runCalls);
        $this->assertSame(1, $ListTool->listCalls);
        $this->assertSame(1, $ListAllTool->listAllCalls);
        $this->assertSame([23], $CronTool->runCronCalls);
        $this->assertContains('Command not found, please type another command', $UnknownTool->output);
    }

    private function runUntilInputIsExhausted(ControllableExecCrons $Tool): void
    {
        try {
            $Tool->commandRead();
            $this->fail('Expected recursive command input to be exhausted.');
        } catch (RuntimeException $Exception) {
            $this->assertSame('missing-input', $Exception->getMessage());
        }
    }
}
