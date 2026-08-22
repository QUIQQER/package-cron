<?php

namespace QUITests\Integration\Cron;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Cron\EventHandler;
use QUI\Cron\Manager;
use QUI\Interfaces\Users\User;
use QUI\Package\Manager as PackageManager;
use ReflectionProperty;

class EventHandlerAutoCreateTest extends TestCase
{
    private const FIXTURE_TITLE = 'phpunit-autocreate-cron';
    private const FIXTURE_PACKAGE = 'quiqqer/cron/tests/integration/QUI/Cron/Fixtures/autocreate-package';

    private ?PackageManager $previousPackageManager = null;

    private ?User $previousSessionUser = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousPackageManager = QUI::$PackageManager;
        $this->previousSessionUser = self::replaceSessionUser(QUI::getUsers()->getSystemUser());
        self::cleanupFixtures();

        $PackageManager = $this->createMock(PackageManager::class);
        $PackageManager->method('getInstalled')
            ->willReturn([['name' => self::FIXTURE_PACKAGE]]);
        $PackageManager->method('getInstalledPackage')
            ->willThrowException(new QUI\Exception('Not a package identifier fixture'));

        QUI::$PackageManager = $PackageManager;
    }

    protected function tearDown(): void
    {
        self::cleanupFixtures();
        QUI::$PackageManager = $this->previousPackageManager;

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
    public function requiredCronIsCreatedOnceWithConfiguredStateAndParameters(): void
    {
        EventHandler::createAutoCreateCrons(null, true);
        EventHandler::createAutoCreateCrons(null, true);

        $rows = self::findFixtureCrons();

        self::assertCount(1, $rows);
        self::assertSame('0', (string)$rows[0]['active']);
        self::assertSame('7', (string)$rows[0]['min']);
        self::assertSame('8', (string)$rows[0]['hour']);
        self::assertSame('2', (string)$rows[0]['dayOfWeek']);
        self::assertSame([[
            'name' => 'limit',
            'value' => '12'
        ]], json_decode($rows[0]['params'], true));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function findFixtureCrons(): array
    {
        $QueryBuilder = QUI::getQueryBuilder();

        return $QueryBuilder
            ->select('*')
            ->from(QUI\Utils\Doctrine::quoteIdentifier(Manager::table()))
            ->where($QueryBuilder->expr()->eq('title', ':title'))
            ->setParameter('title', self::FIXTURE_TITLE)
            ->executeQuery()
            ->fetchAllAssociative();
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
        $rows = self::findFixtureCrons();
        $Connection = QUI::getDataBaseConnection();

        foreach ($rows as $row) {
            $Connection->delete(
                QUI\Utils\Doctrine::quoteIdentifier(Manager::tableHistory()),
                ['cronid' => $row['id']]
            );
            $Connection->delete(
                QUI\Utils\Doctrine::quoteIdentifier(Manager::table()),
                ['id' => $row['id']]
            );
        }
    }
}
