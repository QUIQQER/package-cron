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
    static function clearTempFolder()
    {
        $Temp = QUI::getTemp();
        $Temp->clear();
    }

    /**
     * Clear complete cache
     */
    static function clearCache()
    {
        QUI\Cache\Manager::clearAll();
    }

    /**
     * Purge the cache
     */
    static function purgeCache()
    {
        QUI\Cache\Manager::purge();
    }

    /**
     * Check project sites release dates
     * Activate or deactivate sites
     *
     * @param Array             $params - Cron Parameter
     * @param \QUI\Cron\Manager $CronManager
     *
     * @throws QUI\Exception
     */
    static function realeaseDate($params, $CronManager)
    {
        if (!isset($params['project'])) {
            throw new QUI\Exception('Need a project parameter to search release dates');
        }

        if (!isset($params['lang'])) {
            throw new QUI\Exception('Need a lang parameter to search release dates');
        }


        $Project = QUI::getProject($params['project'], $params['lang']);
        $now = date('Y-m-d H:i:s');

        // search sites with release dates
        $PDO = QUI::getDataBase()->getPDO();

        $deactivate = array();
        $activate = array();


        /**
         * deactivate sites
         */
        $Statment = $PDO->prepare("
            SELECT id
            FROM {$Project->getAttribute('db_table')}
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
            FROM {$Project->getAttribute('db_table')}
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
            'Folgende Seiten wurden deaktiviert: '.implode(',', $deactivate)
        );

        QUI\System\Log::addInfo(
            'Folgende Seiten wurden aktiviert: '.implode(',', $activate)
        );
    }

    /**
     * Send the mail queue
     *
     * @param array             $params
     * @param \QUI\Cron\Manager $CronManager
     */
    static function mailQueue($params, $CronManager)
    {
        $MailQueue = new QUI\Mail\Queue();

        if ($MailQueue->count()) {
            $MailQueue->send();
        }
    }
}