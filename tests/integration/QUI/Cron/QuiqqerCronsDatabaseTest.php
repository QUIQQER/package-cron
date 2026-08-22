<?php

namespace QUITests\Integration\Cron;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Cron\Manager;
use QUI\Cron\QuiqqerCrons;

class QuiqqerCronsDatabaseTest extends TestCase
{
    private const TITLE_PREFIX = 'phpunit-history-cleanup-';

    /** @var array<int, int> */
    private array $cronIds = [];

    private Connection $Connection;

    protected function setUp(): void
    {
        parent::setUp();

        self::cleanupFixtures();
        $this->Connection = QUI::getDataBaseConnection();
        $this->Connection->beginTransaction();

        $cronWithRecentHistory = $this->insertCron('recent');
        $cronWithOldHistory = $this->insertCron('old');
        $this->cronIds = [$cronWithRecentHistory, $cronWithOldHistory];

        $this->insertHistory($cronWithRecentHistory, '2020-01-01 00:00:00');
        $this->insertHistory($cronWithRecentHistory, '2021-01-01 00:00:00');
        $this->insertHistory($cronWithRecentHistory, '2099-01-01 00:00:00');
        $this->insertHistory($cronWithOldHistory, '2020-01-01 00:00:00');
        $this->insertHistory($cronWithOldHistory, '2021-01-01 00:00:00');
    }

    protected function tearDown(): void
    {
        if ($this->Connection->isTransactionActive()) {
            $this->Connection->rollBack();
        }

        self::cleanupFixtures();

        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        self::cleanupFixtures();
    }

    #[Test]
    public function cleanupKeepsNewestHistoryEntryForEveryCron(): void
    {
        QuiqqerCrons::cleanupCronHistory(['deleteAfterWeeks' => 1], new Manager());

        self::assertSame(1, $this->countHistory($this->cronIds[0]));
        self::assertSame(1, $this->countHistory($this->cronIds[1]));

        $QueryBuilder = QUI::getQueryBuilder();
        $remainingOldCronDate = $QueryBuilder
            ->select('lastexec')
            ->from(QUI\Utils\Doctrine::quoteIdentifier(Manager::tableHistory()))
            ->where($QueryBuilder->expr()->eq('cronid', ':cronId'))
            ->setParameter('cronId', $this->cronIds[1])
            ->executeQuery()
            ->fetchOne();

        self::assertSame('2021-01-01 00:00:00', $remainingOldCronDate);
    }

    private function insertCron(string $suffix): int
    {
        $title = self::TITLE_PREFIX . $suffix;
        QUI::getDataBaseConnection()->insert(QUI\Utils\Doctrine::quoteIdentifier(Manager::table()), [
            'active' => 0,
            'exec' => '\\QUITests\\Integration\\Cron\\Fixtures\\ExecutableCron::execute',
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

    private function insertHistory(int $cronId, string $date): void
    {
        QUI::getDataBaseConnection()->insert(
            QUI\Utils\Doctrine::quoteIdentifier(Manager::tableHistory()),
            [
                'cronid' => $cronId,
                'uid' => '0',
                'lastexec' => $date,
                'finish' => $date
            ]
        );
    }

    private function countHistory(int $cronId): int
    {
        $QueryBuilder = QUI::getQueryBuilder();

        return (int)$QueryBuilder
            ->select('COUNT(id)')
            ->from(QUI\Utils\Doctrine::quoteIdentifier(Manager::tableHistory()))
            ->where($QueryBuilder->expr()->eq('cronid', ':cronId'))
            ->setParameter('cronId', $cronId)
            ->executeQuery()
            ->fetchOne();
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
