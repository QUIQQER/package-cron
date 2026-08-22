<?php

namespace QUITests\Unit\Cron;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Config;
use QUI\Cron\Update;
use QUI\Package\Manager as PackageManager;
use QUI\Package\Package;

class UpdateTest extends TestCase
{
    private ?Config $previousConfig = null;

    private ?PackageManager $previousPackageManager = null;

    private string $updatesFile;

    private bool $updatesFileExisted = false;

    private string | false $previousUpdatesFileContent = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousConfig = QUI::$Conf;
        $this->previousPackageManager = QUI::$PackageManager;
        $this->updatesFile = QUI::getPackage('quiqqer/cron')->getVarDir() . 'updates';
        $this->updatesFileExisted = file_exists($this->updatesFile);

        if ($this->updatesFileExisted) {
            $this->previousUpdatesFileContent = file_get_contents($this->updatesFile);
        }
    }

    protected function tearDown(): void
    {
        QUI::$Conf = $this->previousConfig;
        QUI::$PackageManager = $this->previousPackageManager;

        if ($this->updatesFileExisted && $this->previousUpdatesFileContent !== false) {
            file_put_contents($this->updatesFile, $this->previousUpdatesFileContent);
        } elseif (file_exists($this->updatesFile)) {
            unlink($this->updatesFile);
        }

        parent::tearDown();
    }

    #[Test]
    public function availableUpdatesCanBeStoredReadAndCleared(): void
    {
        $updates = [[
            'package' => 'vendor/package',
            'oldVersion' => '1.0.0',
            'version' => '1.1.0'
        ]];

        Update::setAvailableUpdates($updates);

        self::assertSame($updates, Update::getAvailableUpdates());
        self::assertFileExists($this->updatesFile);

        Update::clearUpdateCheck();

        self::assertFileDoesNotExist($this->updatesFile);
        self::assertSame([], Update::getAvailableUpdates());
    }

    #[Test]
    public function invalidAvailableUpdatesFileReturnsEmptyList(): void
    {
        file_put_contents($this->updatesFile, '{invalid');

        self::assertSame([], Update::getAvailableUpdates());
    }

    #[Test]
    public function automaticCheckReturnsWhenItIsDisabled(): void
    {
        $Config = $this->createMock(Config::class);
        $Config->expects(self::once())
            ->method('get')
            ->with('update', 'auto_check')
            ->willReturn(false);

        $Package = $this->createMock(Package::class);
        $Package->expects(self::once())
            ->method('getConfig')
            ->willReturn($Config);

        $PackageManager = $this->createMock(PackageManager::class);
        $PackageManager->expects(self::once())
            ->method('getInstalledPackage')
            ->with('quiqqer/cron')
            ->willReturn($Package);
        $PackageManager->expects(self::never())
            ->method('getOutdated');

        QUI::$PackageManager = $PackageManager;

        Update::check();
    }

    #[Test]
    public function automaticUpdateReturnsWhenItIsDisabled(): void
    {
        $Config = $this->createMock(Config::class);
        $Config->expects(self::once())
            ->method('get')
            ->with('update', 'auto_update')
            ->willReturn(false);

        $Package = $this->createMock(Package::class);
        $Package->expects(self::once())
            ->method('getConfig')
            ->willReturn($Config);

        $PackageManager = $this->createMock(PackageManager::class);
        $PackageManager->expects(self::once())
            ->method('getInstalledPackage')
            ->with('quiqqer/cron')
            ->willReturn($Package);
        $PackageManager->expects(self::never())
            ->method('getOutdated');

        QUI::$PackageManager = $PackageManager;

        Update::update();
    }

    #[Test]
    public function failedUpdateLookupStillRestoresMaintenanceMode(): void
    {
        $maintenanceModeWasRead = false;
        $Config = $this->createMock(Config::class);
        $Config->method('get')
            ->willReturnCallback(static function (
                string $section,
                ?string $key
            ) use (
                &$maintenanceModeWasRead
            ): int {
                if ($section === 'globals' && $key === 'maintenance') {
                    $maintenanceModeWasRead = true;
                }

                return 0;
            });
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
            ->willThrowException(new \RuntimeException('Package lookup failed'));

        $this->replaceQuiServices($Config, $PackageManager);

        Update::updateExecute();

        self::assertTrue($maintenanceModeWasRead);
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
        QUI::$Conf = $Config;
        QUI::$PackageManager = $PackageManager;
    }
}
