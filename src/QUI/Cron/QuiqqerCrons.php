<?php

/**
 * This File contains QUI\Cron\QuiqqerCrons
 */

namespace QUI\Cron;

use DateTime;
use QUI;
use QUI\Database\Exception;
use Throwable;

use function date;
use function date_create;
use function basename;
use function file_exists;
use function file_get_contents;
use function filemtime;
use function is_array;
use function is_dir;
use function json_decode;
use function rename;
use function str_ends_with;
use function time;
use function unlink;

/**
 * Cron Manager
 * - offers the default cron
 */
class QuiqqerCrons
{
    /**
     * Clear the temp folder
     */
    public static function clearTempFolder(): void
    {
        $Temp = QUI::getTemp();
        $Temp->clear();
    }

    /**
     * Clear complete cache
     */
    public static function clearCache(): void
    {
        QUI\Cache\Manager::clearAll();
    }

    /**
     * Purge the cache
     */
    public static function purgeCache(): void
    {
        QUI\Cache\Manager::purge();
    }

    /**
     * Clear the media cache of the administration
     *
     * @throws QUI\Exception
     */
    public static function clearAdminMediaCache(): void
    {
        QUI\Utils\System\File::unlink(VAR_DIR . 'cache/admin/media/');
    }

    /**
     * @param array<string, mixed> $params
     */
    public static function cleanupCronHistory(array $params, Manager $CronManager): void
    {
        $weeks = 8;
        $table = Manager::tableHistory();
        $database = QUI::getDataBaseConnection();

        if (!empty($params['deleteAfterWeeks'])) {
            $weeks = (int)$params['deleteAfterWeeks'];
        }

        try {
            $thresholdDate = new DateTime();
            $thresholdDate->modify("-{$weeks} weeks");
            $thresholdFormatted = $thresholdDate->format('Y-m-d H:i:s');

            $deleteSql = "
                DELETE FROM $table
                WHERE 
                    lastexec < :threshold AND 
                    id NOT IN (
                        SELECT max_id FROM (
                            SELECT MAX(id) AS max_id
                            FROM $table
                            GROUP BY cronid
                        ) AS sub
                    )
            ";

            $database->executeStatement($deleteSql, [
                'threshold' => $thresholdFormatted
            ]);
        } catch (Throwable $exception) {
            QUI\System\Log::addError($exception->getMessage(), [
                'error' => 'at cron execution cleanupCronHistory'
            ]);
        }
    }

    /**
     * delete all unwanted / unneeded sessions
     * @throws Exception
     * @throws QUI\Exception
     */
    public static function clearSessions(): void
    {
        $type = QUI::conf('session', 'type');
        $maxTime = 1400;

        if (QUI::conf('session', 'max_life_time')) {
            $maxTime = (int)QUI::conf('session', 'max_life_time');
        }

        switch ($type) {
            case 'filesystem':
            case 'database':
                break;

            default:
                $type = 'filesystem';
        }

        // filesystem
        if ($type === 'filesystem') {
            // clear native session storage
            $sessionDir = VAR_DIR . 'sessions/';

            if (!is_dir($sessionDir)) {
                return;
            }

            $sessionFiles = QUI\Utils\System\File::readDir($sessionDir);

            // create new folder for the session (file system cache)
            $sessionTempDir = VAR_DIR . 'sessionsBackup/';
            QUI\Utils\System\File::mkdir($sessionTempDir);

            // unlink and move session
            foreach ($sessionFiles as $sessionFile) {
                $fmTime = filemtime($sessionDir . $sessionFile);

                if ($fmTime + $maxTime < time()) {
                    unlink($sessionDir . $sessionFile);
                } else {
                    rename(
                        $sessionDir . $sessionFile,
                        $sessionTempDir . $sessionFile
                    );
                }
            }

            // rename session folders
            QUI::getTemp()->moveToTemp($sessionDir);
            rename($sessionTempDir, $sessionDir);

            return;
        }

        // database
        if ($type === 'database') {
            $table = QUI::getDBTableName('sessions');
            $maxLifetime = time() - $maxTime;

            $QueryBuilder = QUI::getQueryBuilder();
            $QueryBuilder
                ->delete(QUI\Utils\Doctrine::quoteIdentifier($table))
                ->where($QueryBuilder->expr()->lt('session_time', ':maxLifetime'))
                ->setParameter('maxLifetime', $maxLifetime)
                ->executeStatement();
        }
    }

    /**
     * alias -> because release was misspelled
     *
     * @param array<string, mixed> $params
     * @param Manager $CronManager
     * @throws QUI\Exception
     */
    public static function realeaseDate(array $params, Manager $CronManager): void
    {
        self::releaseDate($params, $CronManager);
    }

    /**
     * Check project sites release dates
     * Activate or deactivate sites
     *
     * @param array<string, mixed> $params Cron parameter
     * @param Manager $CronManager
     *
     * @throws QUI\Exception
     */
    public static function releaseDate(array $params, Manager $CronManager): void
    {
        $execCron = function ($project, $lang) {
            $Project = QUI::getProject($project, $lang);
            $now = date('Y-m-d H:i:s');

            // search sites with release dates
            $deactivate = [];
            $activate = [];


            /**
             * deactivate sites
             */
            try {
                $QueryBuilder = QUI::getQueryBuilder();
                $result = $QueryBuilder
                    ->select('id')
                    ->from(QUI\Utils\Doctrine::quoteIdentifier($Project->table()))
                    ->where($QueryBuilder->expr()->eq('active', ':active'))
                    ->andWhere($QueryBuilder->expr()->isNotNull('release_to'))
                    ->andWhere($QueryBuilder->expr()->lt('release_to', ':date'))
                    ->andWhere($QueryBuilder->expr()->eq('auto_release', ':autoRelease'))
                    ->setParameter('active', 1)
                    ->setParameter('date', $now)
                    ->setParameter('autoRelease', 1)
                    ->executeQuery()
                    ->fetchAllAssociative();
            } catch (QUI\Exception | \Doctrine\DBAL\Exception $Exception) {
                QUI\System\Log::writeException($Exception);

                return;
            }

            foreach ($result as $entry) {
                try {
                    $Site = $Project->get((int)$entry['id']);

                    if (method_exists($Site, 'deactivate')) {
                        $Site->deactivate();
                    }

                    $deactivate[] = (int)$entry['id'];
                } catch (QUI\Exception $Exception) {
                    QUI\System\Log::writeException($Exception);
                }
            }


            /**
             * activate sites
             */
            try {
                $QueryBuilder = QUI::getQueryBuilder();
                $result = $QueryBuilder
                    ->select('id', 'release_to')
                    ->from(QUI\Utils\Doctrine::quoteIdentifier($Project->table()))
                    ->where($QueryBuilder->expr()->eq('active', ':active'))
                    ->andWhere($QueryBuilder->expr()->isNotNull('release_from'))
                    ->andWhere($QueryBuilder->expr()->lte('release_from', ':date'))
                    ->andWhere($QueryBuilder->expr()->eq('auto_release', ':autoRelease'))
                    ->setParameter('active', 0)
                    ->setParameter('date', $now)
                    ->setParameter('autoRelease', 1)
                    ->executeQuery()
                    ->fetchAllAssociative();
            } catch (QUI\Exception | \Doctrine\DBAL\Exception $Exception) {
                QUI\System\Log::writeException($Exception);

                return;
            }
            $Now = date_create();

            foreach ($result as $entry) {
                try {
                    // Do not activate sites that have a "release to" date
                    // that is already reached.
                    if (!empty($entry['release_to'])) {
                        $ReleaseTo = date_create($entry['release_to']);

                        if ($ReleaseTo && $ReleaseTo < $Now) {
                            continue;
                        }
                    }

                    $Site = new QUI\Projects\Site\Edit($Project, (int)$entry['id']);
                    $Site->activate();

                    $activate[] = $Site->getId();
                } catch (QUI\Exception $Exception) {
                    QUI\System\Log::writeException($Exception);
                }
            }

            if (!empty($deactivate)) {
                QUI\System\Log::addInfo(
                    QUI::getLocale()->get(
                        'quiqqer/cron',
                        'cron.release.date.log.message.deactivate',
                        ['list' => implode(',', $deactivate)]
                    ),
                    [
                        'project' => $Project->getName(),
                        'lang' => $Project->getLang(),
                    ],
                    'cron'
                );
            }

            if ($activate) {
                QUI\System\Log::addInfo(
                    QUI::getLocale()->get(
                        'quiqqer/cron',
                        'cron.release.date.log.message.activate',
                        ['list' => implode(',', $activate)]
                    ),
                    [
                        'project' => $Project->getName(),
                        'lang' => $Project->getLang(),
                    ],
                    'cron'
                );
            }
        };

        $project = false;
        $lang = false;

        if (isset($params['project'])) {
            $project = $params['project'];
        }

        if (isset($params['lang'])) {
            $lang = $params['lang'];
        }

        if ($lang === false && $project) {
            $Project = QUI::getProject($project);
            $execCron($Project->getName(), $Project->getLang());

            return;
        }

        if ($project && $lang) {
            $Project = QUI::getProject($project, $lang);
            $execCron($Project->getName(), $Project->getLang());

            return;
        }

        $projects = QUI::getProjectManager()->getProjectList();

        foreach ($projects as $Project) {
            $execCron($Project->getName(), $Project->getLang());
        }
    }

    /**
     * Send the mail queue
     *
     * @param array<string, mixed> $params
     * @param Manager $CronManager
     * @throws Exception
     */
    public static function mailQueue(array $params, Manager $CronManager): void
    {
        $MailQueue = new QUI\Mail\Queue();
        $MailQueue->sendAll();
    }

    /**
     * Calculate the sizes of the media folders of each project
     *
     * @param array<string, mixed> $params
     * @param Manager $CronManager
     */
    public static function calculateMediaFolderSizes(array $params, Manager $CronManager): void
    {
        $projects = QUI::getProjectManager()->getProjects(true);

        foreach ($projects as $Project) {
            QUI\Projects\Media\Utils::getMediaFolderSizeForProject($Project, true);
            QUI\Projects\Media\Utils::getMediaCacheFolderSizeForProject($Project, true);
        }
    }

    /**
     * Calculate and caches the sizes of the package-folder.
     * The cached value is used by some system functions.
     *
     * @param array<string, mixed> $params
     * @param Manager $CronManager
     */
    public static function calculatePackageFolderSize(array $params, Manager $CronManager): void
    {
        QUI::getPackageManager()->getPackageFolderSize(true);
    }

    /**
     * Calculate and caches the sizes of the package-folder.
     * The cached value is used by some system functions.
     *
     * @param array<string, mixed> $params
     * @param Manager $CronManager
     */
    public static function calculateCacheFolderSize(array $params, Manager $CronManager): void
    {
        QUI\Cache\Manager::getCacheFolderSize(true);
    }

    /**
     * Calculate and caches the sizes of the package-folder.
     * The cached value is used by some system functions.
     *
     * @param array<string, mixed> $params
     * @param Manager $CronManager
     */
    public static function calculateWholeInstallationFolderSize(array $params, Manager $CronManager): void
    {
        QUI\Utils\Installation::getWholeFolderSize(true);
    }

    /**
     * Counts and caches the amount of files in the QUIQQER installation folder.
     * The cached value is used by some system functions.
     *
     * @param array<string, mixed> $params
     * @param Manager $CronManager
     */
    public static function countAllFilesInInstallation(array $params, Manager $CronManager): void
    {
        QUI\Utils\Installation::getAllFileCount(true);
    }

    /**
     * Calculate and caches the sizes of the VAR-folder.
     * The cached value is used by some system functions.
     *
     * @param array<string, mixed> $params
     * @param Manager $CronManager
     */
    public static function calculateVarFolderSize(array $params, Manager $CronManager): void
    {
        QUI\Utils\Installation::getVarFolderSize(false, true);
    }

    /**
     * Cleanup all expired uploads
     * - older than a day
     */
    public static function cleanupUploads(): void
    {
        $Upload = new QUI\Upload\Manager();
        self::cleanupUploadDirectory($Upload->getDir());
    }

    /**
     * Remove expired upload files and their metadata from an upload directory.
     */
    protected static function cleanupUploadDirectory(string $dir): void
    {
        $folders = QUI\Utils\System\File::readDir($dir);

        $now = time();
        $maxTime = 86400; // seconds -> 1 day

        foreach ($folders as $folder) {
            $files = QUI\Utils\System\File::readDir($dir . $folder);

            foreach ($files as $file) {
                if (!str_ends_with($file, '.json')) {
                    continue;
                }

                $configFile = $dir . $folder . '/' . $file;
                $fileTime = filemtime($configFile);

                if ($now - $fileTime < $maxTime) {
                    continue;
                }

                $config = json_decode((string)file_get_contents($configFile), true);

                if (!is_array($config) || empty($config['file'])) {
                    continue;
                }

                $uploadFile = $dir . $folder . '/' . basename((string)$config['file']);

                if (file_exists($uploadFile)) {
                    unlink($uploadFile);
                }

                unlink($configFile);
            }
        }
    }

    /**
     * Updates all external images
     *
     * @return void
     */
    public static function updateExternalImages(): void
    {
        $projects = QUI::getProjectManager()->getProjectList();

        foreach ($projects as $Project) {
            $Project->getMedia()->updateExternalImages();
        }
    }
}
