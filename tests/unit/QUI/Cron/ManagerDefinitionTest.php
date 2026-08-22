<?php

namespace QUITests\Unit\Cron;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use QUI\Cron\Manager;
use QUITests\Unit\Cron\Fixtures\AccessibleManager;

require_once __DIR__ . '/Fixtures/AccessibleManager.php';

class ManagerDefinitionTest extends TestCase
{
    #[Test]
    public function cliOnlyFlagAcceptsDocumentedTrueValues(): void
    {
        self::assertTrue(Manager::isCliOnlyDefinition(['cliOnly' => true]));
        self::assertTrue(Manager::isCliOnlyDefinition(['cliOnly' => 1]));
        self::assertTrue(Manager::isCliOnlyDefinition(['cliOnly' => '1']));
        self::assertTrue(Manager::isCliOnlyDefinition(['cliOnly' => 'true']));

        self::assertFalse(Manager::isCliOnlyDefinition([]));
        self::assertFalse(Manager::isCliOnlyDefinition(['cliOnly' => false]));
        self::assertFalse(Manager::isCliOnlyDefinition(['cliOnly' => 0]));
        self::assertFalse(Manager::isCliOnlyDefinition(['cliOnly' => 'false']));
    }

    #[Test]
    public function executionContextHonorsCliOnlyDefinitions(): void
    {
        $definitions = [[
            'exec' => '\\Vendor\\Cron::restricted',
            'cliOnly' => true
        ]];
        $WebManager = new AccessibleManager(false, $definitions);
        $CliManager = new AccessibleManager(true, $definitions);

        self::assertFalse($WebManager->canExecute(['exec' => '\\Vendor\\Cron::restricted']));
        self::assertTrue($WebManager->canExecute(['exec' => '\\Vendor\\Cron::regular']));
        self::assertTrue($WebManager->canExecute([]));
        self::assertTrue($CliManager->canExecute(['exec' => '\\Vendor\\Cron::restricted']));
    }

    #[Test]
    public function cronDataCanBeResolvedByTitleExecAndPackageIdentifier(): void
    {
        $definition = [
            'title' => 'Fixture cron',
            'exec' => '\\Vendor\\Cron::fixture'
        ];
        $Manager = new AccessibleManager(availableCrons: [$definition]);

        self::assertSame($definition, $Manager->getCronData('Fixture cron'));
        self::assertSame($definition, $Manager->getCronData('\\Vendor\\Cron::fixture'));
        self::assertFalse($Manager->getCronData('Unknown cron'));
        self::assertTrue($Manager->cronExistsForTest('Fixture cron'));
        self::assertFalse($Manager->cronExistsForTest('Unknown cron'));

        $packageCron = $Manager->getCronData('quiqqer/cron:0');

        self::assertIsArray($packageCron);
        self::assertArrayHasKey('exec', $packageCron);
    }

    #[Test]
    public function storedCronLookupUsesItsTitle(): void
    {
        $Manager = new AccessibleManager(storedCrons: [
            ['title' => 'Stored cron'],
            ['title' => 'Another cron']
        ]);

        self::assertTrue($Manager->isCronSetUp('Stored cron'));
        self::assertFalse($Manager->isCronSetUp('Missing cron'));
    }

    #[Test]
    public function cronExpressionDefaultsMissingDayOfWeek(): void
    {
        $Manager = new AccessibleManager();
        $entry = [
            'min' => '5',
            'hour' => '4',
            'day' => '3',
            'month' => '2'
        ];

        self::assertSame('5 4 3 2 *', $Manager->getExpression($entry));

        $entry['dayOfWeek'] = '1';
        self::assertSame('5 4 3 2 1', $Manager->getExpression($entry));
    }

    #[Test]
    public function stopRequestChangesExecutionState(): void
    {
        $Manager = new AccessibleManager();

        self::assertFalse($Manager->mustStop());

        $Manager->stopAfterCurrentCron();

        self::assertTrue($Manager->mustStop());
    }

    #[Test]
    public function tableNamesUseConfiguredPrefix(): void
    {
        self::assertSame(QUI_DB_PRFX . 'cron', Manager::table());
        self::assertSame(QUI_DB_PRFX . 'cron_history', Manager::tableHistory());
    }

    #[Test]
    public function cronXmlParserReadsDefinitionDetailsAndDefaults(): void
    {
        $definitions = Manager::getCronsFromFile(__DIR__ . '/Fixtures/definition-crons.xml');

        self::assertCount(2, $definitions);
        self::assertSame('Detailed cron', $definitions[0]['title']);
        self::assertSame('Detailed description', $definitions[0]['description']);
        self::assertTrue($definitions[0]['required']);
        self::assertTrue($definitions[0]['cliOnly']);
        self::assertSame([
            [
                'name' => 'limit',
                'type' => 'int',
                'data-qui' => '',
                'desc' => 'Maximum items'
            ]
        ], $definitions[0]['params']);
        self::assertSame([
            [
                'interval' => '0 1 * * *',
                'active' => true,
                'params' => [[
                    'name' => 'project',
                    'value' => '[projectName]'
                ]],
                'scope' => Manager::AUTOCREATE_SCOPE_PROJECTS
            ]
        ], $definitions[0]['autocreate']);

        self::assertFalse($definitions[1]['required']);
        self::assertFalse($definitions[1]['cliOnly']);
        self::assertSame([], $definitions[1]['params']);
        self::assertSame([], $definitions[1]['autocreate']);
    }

    #[Test]
    public function cronXmlParserHandlesMissingOrEmptyFiles(): void
    {
        self::assertSame([], Manager::getCronsFromFile(__DIR__ . '/Fixtures/missing.xml'));
        self::assertSame([], Manager::getCronsFromFile(__DIR__ . '/Fixtures/no-crons.xml'));
        self::assertSame([], Manager::getCronsFromFile(__DIR__ . '/Fixtures/empty-crons.xml'));
    }
}
