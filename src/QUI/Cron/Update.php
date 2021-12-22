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

        if (\count($packages)) {
            file_put_contents(
                QUI::getPackage('quiqqer/cron')->getVarDir() . 'updates',
                json_encode($packages)
            );
        }
    }
}
