<?php

namespace QUITests\Integration\Cron;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Config;
use QUI\Cron\Manager;
use QUI\Cron\QuiqqerCrons;
use QUI\Projects\Project;
use QUI\Projects\Site\Edit;

class QuiqqerCronsReleaseDateTest extends TestCase
{
    private const PROJECT_NAME = 'phpunit-release-date';
    private const PROJECT_LANGUAGE = 'de';
    private const TABLE_NAME = 'phpunit_cron_release_date';

    /** @var array<string, array<string, Project>> */
    private array $previousProjects;

    private Project & MockObject $Project;

    private Edit & MockObject $Site;

    private string $projectTable = self::TABLE_NAME;

    private bool $projectConfigExisted;

    private ?Config $previousProjectConfig = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousProjects = QUI\Projects\Manager::$projects;
        $this->projectConfigExisted = isset(QUI::$Configs['etc/projects.ini']);

        if ($this->projectConfigExisted) {
            $this->previousProjectConfig = QUI::$Configs['etc/projects.ini'];
        }

        $Connection = QUI::getDataBaseConnection();
        $Connection->executeStatement('DROP TEMPORARY TABLE IF EXISTS ' . self::TABLE_NAME);
        $Connection->executeStatement(
            'CREATE TEMPORARY TABLE ' . self::TABLE_NAME . ' ('
            . 'id INT NOT NULL, '
            . 'active INT NOT NULL, '
            . 'release_to DATETIME NULL, '
            . 'release_from DATETIME NULL, '
            . 'auto_release INT NOT NULL'
            . ')'
        );

        $this->Site = $this->createMock(Edit::class);
        $this->Project = $this->createMock(Project::class);
        $this->Project->method('getName')
            ->willReturn(self::PROJECT_NAME);
        $this->Project->method('getLang')
            ->willReturn(self::PROJECT_LANGUAGE);
        $this->Project->method('table')
            ->willReturnCallback(fn(): string => $this->projectTable);
        $this->Project->method('get')
            ->willReturn($this->Site);

        QUI\Projects\Manager::$projects[self::PROJECT_NAME] = [
            '_standard' => $this->Project,
            self::PROJECT_LANGUAGE => $this->Project
        ];
    }

    protected function tearDown(): void
    {
        QUI\Projects\Manager::$projects = $this->previousProjects;

        if ($this->projectConfigExisted && $this->previousProjectConfig !== null) {
            QUI::$Configs['etc/projects.ini'] = $this->previousProjectConfig;
        } else {
            unset(QUI::$Configs['etc/projects.ini']);
        }

        QUI::getDataBaseConnection()->executeStatement(
            'DROP TEMPORARY TABLE IF EXISTS ' . self::TABLE_NAME
        );

        parent::tearDown();
    }

    #[Test]
    public function releaseDateQueriesExplicitProjectAndLanguageWithoutChangingSites(): void
    {
        QuiqqerCrons::releaseDate([
            'project' => self::PROJECT_NAME,
            'lang' => self::PROJECT_LANGUAGE
        ], new Manager());

        self::assertSame(0, $this->countTemporaryRows());
    }

    #[Test]
    public function legacyAliasSupportsProjectDefaultLanguage(): void
    {
        QuiqqerCrons::realeaseDate([
            'project' => self::PROJECT_NAME
        ], new Manager());

        self::assertSame(0, $this->countTemporaryRows());
    }

    #[Test]
    public function expiredActiveSiteIsDeactivatedAndExpiredActivationIsSkipped(): void
    {
        QUI::getDataBaseConnection()->insert(self::TABLE_NAME, [
            'id' => 10,
            'active' => 1,
            'release_to' => '2020-01-01 00:00:00',
            'release_from' => null,
            'auto_release' => 1
        ]);
        QUI::getDataBaseConnection()->insert(self::TABLE_NAME, [
            'id' => 11,
            'active' => 0,
            'release_to' => '2020-01-01 00:00:00',
            'release_from' => '2020-01-01 00:00:00',
            'auto_release' => 1
        ]);
        $this->Project->expects(self::once())
            ->method('get')
            ->with(10)
            ->willReturn($this->Site);
        $this->Site->expects(self::once())
            ->method('deactivate');

        QuiqqerCrons::releaseDate([
            'project' => self::PROJECT_NAME,
            'lang' => self::PROJECT_LANGUAGE
        ], new Manager());

        self::assertSame(2, $this->countTemporaryRows());
    }

    #[Test]
    public function releaseDateProcessesConfiguredProjectList(): void
    {
        $Config = $this->createMock(Config::class);
        $Config->method('toArray')
            ->willReturn([
                self::PROJECT_NAME => ['langs' => self::PROJECT_LANGUAGE]
            ]);
        QUI::$Configs['etc/projects.ini'] = $Config;

        QuiqqerCrons::releaseDate([], new Manager());

        self::assertSame(0, $this->countTemporaryRows());
    }

    #[Test]
    public function releaseDateReturnsWhenProjectTableCannotBeQueried(): void
    {
        $this->projectTable = 'phpunit_missing_release_table';

        QuiqqerCrons::releaseDate([
            'project' => self::PROJECT_NAME,
            'lang' => self::PROJECT_LANGUAGE
        ], new Manager());

        self::assertTrue(true);
    }

    private function countTemporaryRows(): int
    {
        return (int)QUI::getDataBaseConnection()
            ->executeQuery('SELECT COUNT(*) FROM ' . self::TABLE_NAME)
            ->fetchOne();
    }
}
