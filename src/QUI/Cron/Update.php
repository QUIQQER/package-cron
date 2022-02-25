<?php

/**
 * This file contains QUI\Cron\Update
 */

namespace QUI\Cron;

use QUI;

use function count;

/**
 * Update cron
 * - checked if updates are available
 */
class Update
{
    //region check for updates

    /**
     * @return void
     */
    public static function check()
    {
        try {
            $Package = QUI::getPackage('quiqqer/cron');
            $Config  = $Package->getConfig();
        } catch (\Exception $Exception) {
            return;
        }

        if (!$Config->get('update', 'auto_check')) {
            return;
        }

        self::checkExecute();
    }

    /**
     * @return void
     */
    public static function checkExecute()
    {
        try {
            $Packages = QUI::getPackageManager();
            $packages = $Packages->getOutdated(true);
            $file     = QUI::getPackage('quiqqer/cron')->getVarDir() . 'updates';
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            return;
        }

        if (count($packages)) {
            file_put_contents($file, json_encode($packages));

            QUI::getMailManager()->send(
                QUI::conf('mail', 'admin_mail'),
                QUI::getLocale()->get('quiqqer/cron', 'update.mail.updateCheck.subject', [
                    'system' => QUI::conf('globals', 'host')
                ]),
                QUI::getLocale()->get('quiqqer/cron', 'update.mail.updateCheck.description')
            );

            return;
        }

        if (\file_exists($file)) {
            \unlink($file);
        }
    }

    //endregion

    //region execute update

    /**
     * @return void
     */
    public static function update()
    {
        try {
            $Package = QUI::getPackage('quiqqer/cron');
            $Config  = $Package->getConfig();
        } catch (\Exception $Exception) {
            return;
        }

        if (!$Config->get('update', 'auto_update')) {
            return;
        }

        self::updateExecute();
    }

    /**
     * Execute an system update
     *
     * @return void
     */
    public static function updateExecute()
    {
        $Config = QUI::getConfig('etc/conf.ini.php');
        $Config->set('globals', 'maintenance', 1);
        $Config->save();

        try {
            $Packages = QUI::getPackageManager();
            $packages = $Packages->getOutdated(true);
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            return;
        }

        if (!count($packages)) {
            return;
        }

        $updateString = '<ul>';

        foreach ($packages as $package) {
            $package = $package['package'];
            $from    = $package['oldVersion'];
            $to      = $package['version'];

            $updateString .= '<li>' . $package . ': ' . $from . ' -> ' . $to . '</li>';
        }

        $updateString .= '<ul>';


        try {
            $Packages->update();
        } catch (\Exception $Exception) {
            QUI::getMailManager()->send(
                QUI::conf('mail', 'admin_mail'),
                QUI::getLocale()->get('quiqqer/cron', 'update.mail.error.subject'),
                QUI::getLocale()->get('quiqqer/cron', 'update.mail.error.body', [
                    'packages' => $updateString,
                    'host'     => HOST,
                    'ip'       => QUI\Utils\System::getClientIP()
                ])
            );

            return;
        }

        QUI::getMailManager()->send(
            QUI::conf('mail', 'admin_mail'),
            QUI::getLocale()->get('quiqqer/cron', 'update.mail.success.subject'),
            QUI::getLocale()->get('quiqqer/cron', 'update.mail.success.body', [
                'packages' => $updateString,
                'host'     => HOST,
                'ip'       => QUI\Utils\System::getClientIP()
            ])
        );

        $Config->set('globals', 'maintenance', 0);
        $Config->save();
    }

    //endregion

    //region utils

    /**
     * @param array $packages
     * @return void
     */
    public static function setAvailableUpdates(array $packages = [])
    {
        $file = QUI::getPackage('quiqqer/cron')->getVarDir() . 'updates';

        if (count($packages)) {
            file_put_contents($file, json_encode($packages));
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

    //endregion
}
