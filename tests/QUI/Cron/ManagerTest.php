<?php

namespace QUITests\Cron;

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
     */
    private function createExecutionManager(
        array $updateStates = [],
        ?int $stopAfterCronId = null
    ): Manager {
        return new class ($updateStates, $stopAfterCronId) extends Manager {
            /** @var array<int, int> */
            public array $executedCronIds = [];

            /**
             * @param array<int, bool> $updateStates
             */
            public function __construct(
                private array $updateStates,
                private readonly ?int $stopAfterCronId
            ) {
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

        $secondEntry = $this->createEntry();
        $secondEntry['id'] = 2;
        $secondEntry['title'] = 'Cron after update';

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
}
