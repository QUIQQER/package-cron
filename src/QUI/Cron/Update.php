<?php

/**
 * This file contains QUI\Cron\Update
 */

namespace QUI\Cron;

use QUI;

/**
 * Update cron
 * - checked if updates are available
 */
class Update
{
    /**
     * @return void
     * @throws QUI\Exception
     */
    public static function check()
    {
        try {
            $Packages = QUI::getPackageManager();
            $packages = $Packages->getOutdated(true);
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            return;
        }

        $file = QUI::getPackage('quiqqer/cron')->getVarDir() . 'updates';

        if (\count($packages)) {
            file_put_contents($file, json_encode($packages));
            return;
        }

        if (\file_exists($file)) {
            \unlink($file);
        }
    }

    /**
     * @return array
     * @throws QUI\Exception
     */
    public static function getAvailableUpdates(): array
    {
        $file = QUI::getPackage('quiqqer/cron')->getVarDir() . 'updates';

        if (!file_exists($file)) {
            return [];
        }

        return json_decode(file_get_contents($file), true);
    }

    /**
     * If update file exists, this file will be deleted
     *
     * @return void
     * @throws QUI\Exception
     */
    public static function clearUpdateCheck()
    {
        $file = QUI::getPackage('quiqqer/cron')->getVarDir() . 'updates';

        if (file_exists($file)) {
            unlink($file);
        }
    }
}
