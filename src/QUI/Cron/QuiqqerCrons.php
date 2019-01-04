<?php

/**
 * This File contains QUI\Cron\QuiqqerCrons
 */

namespace QUI\Cron;

use QUI;

/**
 * Cron Manager
 *
 * @author www.pcsg.de (Henning Leutz)
 */
class QuiqqerCrons
{
    /**
     * Clear the temp folder
     */
    public static function clearTempFolder()
    {
        $Temp = QUI::getTemp();
        $Temp->clear();
    }

    /**
     * Clear complete cache
     */
    public static function clearCache()
    {
        QUI\Cache\Manager::clearAll();
    }

    /**
     * Purge the cache
     */
    public static function purgeCache()
    {
        QUI\Cache\Manager::purge();
    }

    /**
     * Clear the media cache of the administration
     */
    public static function clearAdminMediaCache()
    {
        QUI\Utils\System\File::unlink(VAR_DIR . 'cache/admin/media/');
    }

    /**
     * delete all unwanted / unneeded sessions
     */
    public static function clearSessions()
    {
        // clear nativ session storage
        $sessionDir = VAR_DIR . 'sessions/';

        if (!is_dir($sessionDir)) {
            return;
        }

        $sessionFiles = QUI\Utils\System\File::readDir($sessionDir);
        $maxtime      = 1400;

        if (QUI::conf('session', 'max_life_time')) {
            $maxtime = (int)QUI::conf('session', 'max_life_time');
        }

        foreach ($sessionFiles as $sessionFile) {
            $fmtime = filemtime($sessionDir . $sessionFile);

            if ($fmtime + $maxtime < time()) {
                unlink($sessionDir . $sessionFile);
            }
        }
    }

    /**
     * Check project sites release dates
     * Activate or deactivate sites
     *
     * @param array $params - Cron Parameter
     * @param \QUI\Cron\Manager $CronManager
     *
     * @throws QUI\Exception
     */
    public static function realeaseDate($params, $CronManager)
    {
        if (!isset($params['project'])) {
            throw new QUI\Exception('Need a project parameter to search release dates');
        }

        if (!isset($params['lang'])) {
            throw new QUI\Exception('Need a lang parameter to search release dates');
        }


        $Project = QUI::getProject($params['project'], $params['lang']);
        $now     = date('Y-m-d H:i:s');

        // search sites with release dates
        $PDO = QUI::getDataBase()->getPDO();

        $deactivate = array();
        $activate   = array();


        /**
         * deactivate sites
         */
        $Statment = $PDO->prepare("
            SELECT id
            FROM {$Project->table()}
            WHERE active = 1 AND
                  release_from != :empty AND
                  release_to != :empty AND

                  (release_from > :date OR release_to < :date)
            ;
        ");

        $Statment->bindValue(':date', $now, \PDO::PARAM_STR);
        $Statment->bindValue(':empty', '0000-00-00 00:00:00', \PDO::PARAM_STR);
        $Statment->execute();

        $result = $Statment->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($result as $entry) {
            try {
                $Site = $Project->get((int)$entry['id']);
                $Site->deactivate();

                $deactivate[] = (int)$entry['id'];
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }


        /**
         * activate sites
         */
        $Statment = $PDO->prepare("
            SELECT id
            FROM {$Project->table()}
            WHERE active = 0 AND
                  release_from != :empty AND
                  release_to != :empty AND

                  release_to >= :date AND
                  release_from <= :date
            ;
        ");

        $Statment->bindValue(':date', $now, \PDO::PARAM_STR);
        $Statment->bindValue(':empty', '0000-00-00 00:00:00', \PDO::PARAM_STR);
        $Statment->execute();

        $result = $Statment->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($result as $entry) {
            try {
                $Site = $Project->get((int)$entry['id']);
                $Site->activate();

                $activate[] = (int)$entry['id'];
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }

        QUI\System\Log::addInfo(
            'Folgende Seiten wurden deaktiviert: ' . implode(',', $deactivate)
        );

        QUI\System\Log::addInfo(
            'Folgende Seiten wurden aktiviert: ' . implode(',', $activate)
        );
    }

    /**
     * Send the mail queue
     *
     * @param array $params
     * @param \QUI\Cron\Manager $CronManager
     */
    public static function mailQueue($params, $CronManager)
    {
        $MailQueue = new QUI\Mail\Queue();
        $MailQueue->sendAll();
    }


    /**
     * Calculate the sizes of the media folders of each project
     *
     * @param $params
     * @param Manager $CronManager
     */
    public static function calculateMediaFolderSizes($params, Manager $CronManager)
    {
        try {
            $projects = QUI::getProjectManager()::getProjects(true);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError('Something went wrong getting all projects to calculate the media folder sizes');
            QUI\System\Log::writeException($Exception);
            return;
        }

        foreach ($projects as $Project) {
            QUI\Projects\Media\Utils::getMediaFolderSizeForProject($Project, true);
            QUI\Projects\Media\Utils::getMediaCacheFolderSizeForProject($Project, true);
        }
    }


    /**
     * Calculate and caches the sizes of the package-folder.
     * The cached value is used by some system functions.
     *
     * @param $params
     * @param Manager $CronManager
     */
    public static function calculatePackageFolderSize($params, Manager $CronManager)
    {
        QUI::getPackageManager()->getPackageFolderSize(true);
    }


    /**
     * Calculate and caches the sizes of the package-folder.
     * The cached value is used by some system functions.
     *
     * @param $params
     * @param Manager $CronManager
     */
    public static function calculateCacheFolderSize($params, Manager $CronManager)
    {
        QUI\Cache\Manager::getCacheFolderSize(true);
    }


    /**
     * Calculate and caches the sizes of the package-folder.
     * The cached value is used by some system functions.
     *
     * @param $params
     * @param Manager $CronManager
     */
    public static function calculateWholeInstallationFolderSize($params, Manager $CronManager)
    {
        QUI\Utils\Installation::getWholeFolderSize(true);
    }


    /**
     * Counts and caches the amount of files in the QUIQQER installation folder.
     * The cached value is used by some system functions.
     *
     * @param $params
     * @param Manager $CronManager
     */
    public static function countAllFilesInInstallation($params, Manager $CronManager)
    {
        QUI\Utils\Installation::getAllFileCount(true);
    }
}
