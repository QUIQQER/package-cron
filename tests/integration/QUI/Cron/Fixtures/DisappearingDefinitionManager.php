<?php

namespace QUITests\Integration\Cron\Fixtures;

use QUI\Cron\Manager;

class DisappearingDefinitionManager extends Manager
{
    private int $definitionCalls = 0;

    public function getCronData(string $cron): array | false
    {
        $this->definitionCalls++;

        if ($this->definitionCalls > 1) {
            return false;
        }

        return [
            'title' => $cron,
            'exec' => '\\Vendor\\Fixture::execute'
        ];
    }
}
