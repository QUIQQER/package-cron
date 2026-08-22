<?php

namespace QUITests\Unit\Cron;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Config;
use QUI\Cron\Update;
use QUI\Package\Manager as PackageManager;

class UpdateTest extends TestCase
{
    private ?Config $previousConfig = null;

    private ?PackageManager $previousPackageManager = null;

    protected function tearDown(): void
    {
        QUI::$Conf = $this->previousConfig;
        QUI::$PackageManager = $this->previousPackageManager;

        parent::tearDown();
    }

    #[Test]
    public function updateWithoutPackagesRestoresPreviousMaintenanceMode(): void
    {
        $Config = $this->createMock(Config::class);
        $Config->expects(self::once())
            ->method('get')
            ->with('globals', 'maintenance')
            ->willReturn(0);
        $Config->expects(self::exactly(2))
            ->method('set')
            ->willReturnMap([
                ['globals', 'maintenance', 1, true],
                ['globals', 'maintenance', 0, true]
            ]);
        $Config->expects(self::exactly(2))
            ->method('save');

        $PackageManager = $this->createMock(PackageManager::class);
        $PackageManager->expects(self::once())
            ->method('getOutdated')
            ->with(true)
            ->willReturn([]);
        $PackageManager->expects(self::never())
            ->method('update');

        $this->replaceQuiServices($Config, $PackageManager);

        Update::updateExecute();
    }

    private function replaceQuiServices(
        Config & MockObject $Config,
        PackageManager & MockObject $PackageManager
    ): void {
        $this->previousConfig = QUI::$Conf;
        $this->previousPackageManager = QUI::$PackageManager;

        QUI::$Conf = $Config;
        QUI::$PackageManager = $PackageManager;
    }
}
