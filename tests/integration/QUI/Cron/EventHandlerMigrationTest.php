<?php

namespace QUITests\Integration\Cron;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Cron\EventHandler;
use QUI\System\Console\Tools\MigrationV2;

class EventHandlerMigrationTest extends TestCase
{
    #[Test]
    public function migrationProcessesHistoryWithinRollbackTransaction(): void
    {
        $Connection = QUI::getDataBaseConnection();
        $Connection->beginTransaction();
        $Console = $this->createMock(MigrationV2::class);
        $Console->expects(self::once())
            ->method('writeLn')
            ->with('- Migrate cron history');

        try {
            EventHandler::onQuiqqerMigrationV2($Console);
            self::assertTrue(true);
        } finally {
            if ($Connection->isTransactionActive()) {
                $Connection->rollBack();
            }
        }
    }
}
