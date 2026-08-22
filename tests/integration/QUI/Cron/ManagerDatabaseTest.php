<?php

namespace QUITests\Integration\Cron;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Cron\Manager;

class ManagerDatabaseTest extends TestCase
{
    private const FIXTURE_TITLE = 'phpunit-cron-manager-pagination';

    private int $cronId;

    /** @var array<int, int> */
    private array $historyIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        self::cleanupFixtures();

        $Connection = QUI::getDataBaseConnection();
        $Connection->insert(QUI\Utils\Doctrine::quoteIdentifier(Manager::table()), [
            'active' => 0,
            'exec' => '\\QUITests\\Integration\\Cron\\FixtureCron::execute',
            'title' => self::FIXTURE_TITLE,
            'min' => 0,
            'hour' => 0,
            'day' => '*',
            'month' => '*',
            'dayOfWeek' => '*',
            'params' => '[]'
        ]);

        $QueryBuilder = QUI::getQueryBuilder();
        $this->cronId = (int)$QueryBuilder
            ->select('id')
            ->from(QUI\Utils\Doctrine::quoteIdentifier(Manager::table()))
            ->where($QueryBuilder->expr()->eq('title', ':title'))
            ->setParameter('title', self::FIXTURE_TITLE)
            ->executeQuery()
            ->fetchOne();

        for ($second = 1; $second <= 5; $second++) {
            $Connection->insert(QUI\Utils\Doctrine::quoteIdentifier(Manager::tableHistory()), [
                'cronid' => $this->cronId,
                'uid' => '0',
                'lastexec' => sprintf('2099-01-01 00:00:%02d', $second),
                'finish' => sprintf('2099-01-01 00:00:%02d', $second)
            ]);

            $QueryBuilder = QUI::getQueryBuilder();
            $this->historyIds[] = (int)$QueryBuilder
                ->select('id')
                ->from(QUI\Utils\Doctrine::quoteIdentifier(Manager::tableHistory()))
                ->where($QueryBuilder->expr()->eq('cronid', ':cronId'))
                ->andWhere($QueryBuilder->expr()->eq('lastexec', ':lastExecution'))
                ->setParameter('cronId', $this->cronId)
                ->setParameter('lastExecution', sprintf('2099-01-01 00:00:%02d', $second))
                ->executeQuery()
                ->fetchOne();
        }
    }

    protected function tearDown(): void
    {
        self::cleanupFixtures();

        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        self::cleanupFixtures();
    }

    #[Test]
    public function historyPaginationUsesPageSizeForOffset(): void
    {
        $Manager = new Manager();
        $secondPage = $Manager->getHistoryList([
            'page' => 2,
            'perPage' => 2
        ]);

        self::assertSame(
            [$this->historyIds[2], $this->historyIds[1]],
            array_map(
                static fn(array $entry): int => (int)$entry['id'],
                $secondPage
            )
        );
    }

    private static function cleanupFixtures(): void
    {
        $QueryBuilder = QUI::getQueryBuilder();
        $cronIds = $QueryBuilder
            ->select('id')
            ->from(QUI\Utils\Doctrine::quoteIdentifier(Manager::table()))
            ->where($QueryBuilder->expr()->eq('title', ':title'))
            ->setParameter('title', self::FIXTURE_TITLE)
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
