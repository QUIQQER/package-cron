<?php

namespace QUITests\Integration\Cron\Console;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Cron\Manager;
use QUI\Interfaces\Users\User;
use ReflectionProperty;
use QUITests\Integration\Cron\Console\Fixtures\OutputExecCrons;
use QUITests\Integration\Cron\Fixtures\ExecutableCron;

require_once __DIR__ . '/Fixtures/OutputExecCrons.php';
require_once dirname(__DIR__) . '/Fixtures/ExecutableCron.php';

class ExecCronsDatabaseTest extends TestCase
{
    private const TITLE_PREFIX = 'phpunit-console-cron-';
    private const ACTIVE_EXEC = '\\QUITests\\Integration\\Cron\\Fixtures\\ExecutableCron::execute';
    private const INACTIVE_EXEC = '\\QUITests\\Integration\\Cron\\Fixtures\\ExecutableCron::inactive';

    private int $activeCronId;

    private int $inactiveCronId;

    private ?User $previousSessionUser = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousSessionUser = self::replaceSessionUser(QUI::getUsers()->getSystemUser());
        ExecutableCron::$calls = [];
        self::cleanupFixtures();

        $this->activeCronId = $this->insertCron('active', self::ACTIVE_EXEC, 1);
        $this->inactiveCronId = $this->insertCron('inactive', self::INACTIVE_EXEC, 0);
    }

    protected function tearDown(): void
    {
        self::cleanupFixtures();

        if ($this->previousSessionUser !== null) {
            self::replaceSessionUser($this->previousSessionUser);
        }

        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        self::cleanupFixtures();
    }

    #[Test]
    public function activeListOnlyPrintsActiveCrons(): void
    {
        $Tool = new OutputExecCrons();
        $Tool->listCrons();
        $output = implode("\n", $Tool->output);

        self::assertStringContainsString('ID: ' . $this->activeCronId, $output);
        self::assertStringContainsString(self::ACTIVE_EXEC, $output);
        self::assertStringNotContainsString(self::INACTIVE_EXEC, $output);
    }

    #[Test]
    public function completeListPrintsActiveAndInactiveCrons(): void
    {
        $Tool = new OutputExecCrons();
        $Tool->listAllCrons();
        $output = implode("\n", $Tool->output);

        self::assertStringContainsString('ID: ' . $this->activeCronId, $output);
        self::assertStringContainsString('ID: ' . $this->inactiveCronId, $output);
        self::assertStringContainsString(self::ACTIVE_EXEC, $output);
        self::assertStringContainsString(self::INACTIVE_EXEC, $output);
    }

    #[Test]
    public function specificCronCanBeExecuted(): void
    {
        $Tool = new OutputExecCrons();

        $Tool->runCron($this->activeCronId);

        self::assertCount(1, ExecutableCron::$calls);
        self::assertStringContainsString(
            'Execute Cron: ' . $this->activeCronId,
            implode("\n", $Tool->output)
        );
    }

    #[Test]
    public function specificCronRequiresNumericExistingId(): void
    {
        $Tool = new OutputExecCrons();

        try {
            $Tool->runCron();
            self::fail('Expected a non-numeric cron ID to be rejected.');
        } catch (QUI\Exception $Exception) {
            self::assertSame('Cron ID must be an integer', $Exception->getMessage());
        }

        $this->expectException(QUI\Exception::class);
        $this->expectExceptionMessage('Cron not found');

        $Tool->runCron(PHP_INT_MAX);
    }

    #[Test]
    public function unlockReportsWhenNoExecutionLockExists(): void
    {
        $Package = QUI::getPackage('quiqqer/cron');

        if (QUI\Lock\Locker::isLocked($Package, Manager::EXECUTION_LOCK_KEY, null, false)) {
            self::markTestSkipped('The cron execution lock is currently in use.');
        }

        $Tool = new OutputExecCrons();
        $Tool->unlock();

        self::assertContains('No cron execution lock found.', $Tool->output);
    }

    private function insertCron(string $suffix, string $exec, int $active): int
    {
        $Connection = QUI::getDataBaseConnection();
        $title = self::TITLE_PREFIX . $suffix;

        $Connection->insert(QUI\Utils\Doctrine::quoteIdentifier(Manager::table()), [
            'active' => $active,
            'exec' => $exec,
            'title' => $title,
            'min' => 0,
            'hour' => 0,
            'day' => '*',
            'month' => '*',
            'dayOfWeek' => '*',
            'params' => '[]'
        ]);

        $QueryBuilder = QUI::getQueryBuilder();

        return (int)$QueryBuilder
            ->select('id')
            ->from(QUI\Utils\Doctrine::quoteIdentifier(Manager::table()))
            ->where($QueryBuilder->expr()->eq('title', ':title'))
            ->setParameter('title', $title)
            ->executeQuery()
            ->fetchOne();
    }

    private static function replaceSessionUser(User $User): ?User
    {
        $Users = QUI::getUsers();
        $Property = new ReflectionProperty($Users, 'Session');
        $Property->setAccessible(true);

        $PreviousUser = $Property->getValue($Users);
        $Property->setValue($Users, $User);

        return $PreviousUser instanceof User ? $PreviousUser : null;
    }

    private static function cleanupFixtures(): void
    {
        $QueryBuilder = QUI::getQueryBuilder();
        $cronIds = $QueryBuilder
            ->select('id')
            ->from(QUI\Utils\Doctrine::quoteIdentifier(Manager::table()))
            ->where($QueryBuilder->expr()->like('title', ':title'))
            ->setParameter('title', self::TITLE_PREFIX . '%')
            ->executeQuery()
            ->fetchFirstColumn();

        $Connection = QUI::getDataBaseConnection();

        foreach ($cronIds as $cronId) {
            $Connection->delete(
                QUI\Utils\Doctrine::quoteIdentifier(Manager::tableHistory()),
                ['cronid' => $cronId]
            );
            $Connection->delete(
                QUI\Utils\Doctrine::quoteIdentifier(Manager::table()),
                ['id' => $cronId]
            );
        }
    }
}
