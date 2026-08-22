<?php

namespace QUITests\Integration\Cron;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use QUI;

require_once __DIR__ . '/Fixtures/AccessibleEventHandler.php';

class EventHandlerScopeTest extends TestCase
{
    #[Test]
    public function languageScopeReplacesValuePlaceholderAndPreservesParameterShape(): void
    {
        $params = [[
            'name' => 'lang',
            'value' => 'language-[lang]'
        ]];
        $expected = [];

        foreach (QUI::availableLanguages() as $language) {
            $expected[] = [[
                'name' => 'lang',
                'value' => 'language-' . $language
            ]];
        }

        self::assertSame($expected, AccessibleEventHandler::getLanguageScopeParams($params));
    }

    #[Test]
    public function projectScopeReplacesValuePlaceholdersAndPreservesParameterShape(): void
    {
        $params = [
            [
                'name' => 'project',
                'value' => '[projectName]'
            ],
            [
                'name' => 'lang',
                'value' => '[projectLang]'
            ]
        ];
        $expected = [];

        foreach (QUI::getProjectManager()->getProjects(true) as $Project) {
            foreach ($Project->getLanguages() as $language) {
                $expected[] = [
                    [
                        'name' => 'project',
                        'value' => $Project->getName()
                    ],
                    [
                        'name' => 'lang',
                        'value' => $language
                    ]
                ];
            }
        }

        self::assertSame($expected, AccessibleEventHandler::getProjectScopeParams($params));
    }
}
