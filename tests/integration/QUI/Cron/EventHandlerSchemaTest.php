<?php

namespace QUITests\Integration\Cron;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Cron\EventHandler;
use ReflectionMethod;

class EventHandlerSchemaTest extends TestCase
{
    private const TABLE_NAME = 'phpunit_cron_schema';

    protected function setUp(): void
    {
        parent::setUp();

        $Connection = QUI::getDataBaseConnection();
        $Connection->executeStatement('DROP TABLE IF EXISTS ' . self::TABLE_NAME);
        $Connection->executeStatement(
            'CREATE TABLE ' . self::TABLE_NAME . ' (title VARCHAR(10) NULL)'
        );
    }

    protected function tearDown(): void
    {
        QUI::getDataBaseConnection()->executeStatement(
            'DROP TABLE IF EXISTS ' . self::TABLE_NAME
        );

        parent::tearDown();
    }

    #[Test]
    public function stringColumnLengthIsUpdatedAndMissingColumnIsIgnored(): void
    {
        $Method = new ReflectionMethod(EventHandler::class, 'ensureStringColumnLength');

        $Method->invoke(null, self::TABLE_NAME, 'missing_column', 1000);
        $Method->invoke(null, self::TABLE_NAME, 'title', 1000);

        $Column = QUI::getSchemaManager()
            ->introspectTable(self::TABLE_NAME)
            ->getColumn('title');

        self::assertSame(1000, $Column->getLength());

        $Method->invoke(null, self::TABLE_NAME, 'title', 1000);
    }
}
