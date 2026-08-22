<?php

namespace QUITests\Unit\Cron\Fixtures;

use QUI\Cron\QuiqqerCrons;

class AccessibleQuiqqerCrons extends QuiqqerCrons
{
    public static function cleanupDirectory(string $directory): void
    {
        parent::cleanupUploadDirectory($directory);
    }
}
