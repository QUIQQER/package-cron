<?php

namespace QUITests\Unit\Cron;

use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use QUI\Cron\Manager;

class ManagerTest extends TestCase
{
    private function createManager(DateTimeImmutable $currentTime): Manager
    {
        return new class ($currentTime) extends Manager {
            protected DateTimeImmutable $currentTime;

            public function __construct(DateTimeImmutable $currentTime)
            {
                $this->currentTime = $currentTime;
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
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function createEntry(): array
    {
        return [
            'id' => 60,
            'title' => 'Daily cron',
            'exec' => '\\Vendor\\Package\\Cron::execute',
            'min' => '0',
            'hour' => '22',
            'day' => '*',
            'month' => '*',
            'dayOfWeek' => '*',
            'createDate' => '2025-06-30 15:00:00',
            'lastexec' => null
        ];
    }

    /**
     * @param array<int, bool> $updateStates
     * @param array<int, array<string, mixed>> $availableCrons
     */
    private function createExecutionManager(
        array $updateStates = [],
        ?int $stopAfterCronId = null,
        bool $cliExecution = true,
        array $availableCrons = []
    ): Manager {
        return new class ($updateStates, $stopAfterCronId, $cliExecution, $availableCrons) extends Manager {
            /** @var array<int, int> */
            public array $executedCronIds = [];

            /**
             * @param array<int, bool> $updateStates
             */
            public function __construct(
                private array $updateStates,
                private readonly ?int $stopAfterCronId,
                private readonly bool $cliExecution,
                private readonly array $availableCrons
            ) {
            }

            public function getAvailableCrons(): array
            {
                return $this->availableCrons;
            }

            protected function isCliExecution(): bool
            {
                return $this->cliExecution;
            }

            protected function isSystemUpdateRunning(): bool
            {
                return array_shift($this->updateStates) ?? false;
            }

            /**
             * @param array<string, mixed> $entry
             */
            protected function shouldExecuteCron(
                array $entry,
                DateTimeInterface $lastExecutionDate
            ): bool {
                return true;
            }

            public function executeCron(int $cronId): static
            {
                $this->executedCronIds[] = $cronId;

                if ($cronId === $this->stopAfterCronId) {
                    $this->stopAfterCurrentCron();
                }

                return $this;
            }

            /**
             * @param array<int, array<string, mixed>> $entries
             */
            public function executeEntries(array $entries): void
            {
                $this->executeCronList($entries, new DateTimeImmutable('+1 hour'));
            }
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function createExecutionEntries(): array
    {
        $firstEntry = $this->createEntry();
        $firstEntry['id'] = 1;
        $firstEntry['title'] = 'Automatic update';
        $firstEntry['exec'] = '\\Vendor\\Package\\Cron::cliOnly';

        $secondEntry = $this->createEntry();
        $secondEntry['id'] = 2;
        $secondEntry['title'] = 'Cron after update';
        $secondEntry['exec'] = '\\Vendor\\Package\\Cron::regular';

        return [$firstEntry, $secondEntry];
    }

    #[Test]
    public function cronWithoutLastExecutionDoesNotRunBeforeScheduledMinute(): void
    {
        $Manager = $this->createManager(
            new DateTimeImmutable('2025-06-30 16:26:00')
        );
        $entry = $this->createEntry();

        $this->assertFalse(
            $Manager->isCronDue(
                $entry,
                new DateTimeImmutable($entry['createDate'])
            )
        );
    }

    #[Test]
    public function cronWithoutLastExecutionRunsAtScheduledMinute(): void
    {
        $Manager = $this->createManager(
            new DateTimeImmutable('2025-06-30 22:00:00')
        );
        $entry = $this->createEntry();

        $this->assertTrue(
            $Manager->isCronDue(
                $entry,
                new DateTimeImmutable($entry['createDate'])
            )
        );
    }

    #[Test]
    public function cronWithLastExecutionDoesNotRunBeforeNextScheduledMinute(): void
    {
        $Manager = $this->createManager(
            new DateTimeImmutable('2025-07-01 21:55:00')
        );
        $entry = $this->createEntry();

        $this->assertFalse(
            $Manager->isCronDue(
                $entry,
                new DateTimeImmutable('2025-06-30 22:00:00')
            )
        );
    }

    #[Test]
    public function cronWithLastExecutionRunsIfScheduledMinuteWasMissed(): void
    {
        $Manager = $this->createManager(
            new DateTimeImmutable('2025-07-01 22:03:00')
        );
        $entry = $this->createEntry();

        $this->assertTrue(
            $Manager->isCronDue(
                $entry,
                new DateTimeImmutable('2025-06-30 22:00:00')
            )
        );
    }

    #[Test]
    public function cronWithLastExecutionDoesNotRunTwiceForSameSchedule(): void
    {
        $Manager = $this->createManager(
            new DateTimeImmutable('2025-07-01 22:03:00')
        );
        $entry = $this->createEntry();

        $this->assertFalse(
            $Manager->isCronDue(
                $entry,
                new DateTimeImmutable('2025-07-01 22:00:00')
            )
        );
    }

    #[Test]
    public function cronExecutionStopsAfterCurrentCronRequestsIt(): void
    {
        $Manager = $this->createExecutionManager([], 1);

        $Manager->executeEntries($this->createExecutionEntries());

        $this->assertSame([1], $Manager->executedCronIds);
    }

    #[Test]
    public function cronExecutionStopsWhenSystemUpdateStartsBetweenCrons(): void
    {
        $Manager = $this->createExecutionManager([false, true]);

        $Manager->executeEntries($this->createExecutionEntries());

        $this->assertSame([1], $Manager->executedCronIds);
    }

    #[Test]
    public function cronExecutionDoesNotStartWhileSystemUpdateIsRunning(): void
    {
        $Manager = $this->createExecutionManager([true]);

        $Manager->executeEntries($this->createExecutionEntries());

        $this->assertSame([], $Manager->executedCronIds);
    }

    #[Test]
    public function cronTypeIsDeterminedFromDefinitionMetadata(): void
    {
        $this->assertSame(
            Manager::CRON_TYPE_SYSTEM,
            Manager::getCronType(['required' => true, 'autocreate' => []])
        );
        $this->assertSame(
            Manager::CRON_TYPE_SYSTEM,
            Manager::getCronType(['required' => false, 'autocreate' => [['interval' => '0 0 * * *']]])
        );
        $this->assertSame(
            Manager::CRON_TYPE_CUSTOM,
            Manager::getCronType(['required' => false, 'autocreate' => []])
        );
    }

    #[Test]
    public function cliOnlyFlagIsReadFromCronXmlAndDefaultsToFalse(): void
    {
        $crons = Manager::getCronsFromFile(__DIR__ . '/Fixtures/cli-only-crons.xml');

        $this->assertFalse($crons[0]['cliOnly']);
        $this->assertTrue($crons[1]['cliOnly']);
        $this->assertTrue($crons[2]['cliOnly']);
        $this->assertFalse($crons[3]['cliOnly']);
    }

    #[Test]
    public function webExecutionSkipsCliOnlyCrons(): void
    {
        $Manager = $this->createExecutionManager(
            cliExecution: false,
            availableCrons: [[
                'exec' => '\\Vendor\\Package\\Cron::cliOnly',
                'cliOnly' => true
            ]]
        );

        $Manager->executeEntries($this->createExecutionEntries());

        $this->assertSame([2], $Manager->executedCronIds);
    }

    #[Test]
    public function webExecutionAllowsCronsWithoutCliOnlyFlag(): void
    {
        $Manager = $this->createExecutionManager(cliExecution: false);

        $Manager->executeEntries($this->createExecutionEntries());

        $this->assertSame([1, 2], $Manager->executedCronIds);
    }

    #[Test]
    public function cliExecutionAllowsCliOnlyCrons(): void
    {
        $Manager = $this->createExecutionManager(
            availableCrons: [[
                'exec' => '\\Vendor\\Package\\Cron::cliOnly',
                'cliOnly' => true
            ]]
        );

        $Manager->executeEntries($this->createExecutionEntries());

        $this->assertSame([1, 2], $Manager->executedCronIds);
    }
}
