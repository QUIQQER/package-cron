<?php

/**
 * This File contains QUI\Cron\Manager
 */

namespace QUI\Cron;

use QUI;
use QUI\Permissions\Permission;
use Cron\CronExpression;

/**
 * Cron Manager
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @error  1001 - Cannot add Cron. Cron not exists
 * @error  1002 - Cannot edit Cron. Cron command not exists
 */
class Manager
{
    /**
     * Flag that indicates if a cron.log is written
     *
     * @var bool
     */
    protected static $writeCronLog = null;

    /**
     * Data about the current runtime
     *
     * @var array
     */
    protected static $runtime = [
        'currentCronTitle' => '',
        'currentCronId'    => 0,
        'finished'         => 0,
        'total'            => 0,
        'startAll'         => false,
        'startCurrent'     => false,
        'lockEnd'          => false
    ];

    /**
     * @var bool
     */
    protected static $lockTimeoutNotificationSent = false;

    /**
     * Add a cron
     *
     * @param string $cron - Name of the Cron
     * @param string $min - On which minute should it start
     * @param string $hour - On which hour should it start
     * @param string $day - On which day should it start
     * @param string $month - On which month should it start
     * @param string $dayOfWeek - day of week (0 - 6) (0 to 6 are Sunday to Saturday,
     *                          or use names; 7 is Sunday, the same as 0)
     * @param array $params - Cron Parameter
     *
     * @throws QUI\Exception
     */
    public function add($cron, $min, $hour, $day, $month, $dayOfWeek, $params = [])
    {
        Permission::checkPermission('quiqqer.cron.add');

        if (!$this->cronExists($cron)) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/cron', 'exception.cron.1001'),
                1001
            );
        }

        $cronData = $this->getCronData($cron);

        if (!is_array($params)) {
            $params = [];
        }

        if (!empty($params['exec'])) {
            $cronData['exec'] = $params['exec'];
            unset($params['exec']);
        }

        QUI::getDataBase()->insert($this->table(), [
            'active'    => 1,
            'exec'      => $cronData['exec'],
            'title'     => $cronData['title'],
            'min'       => $min,
            'hour'      => $hour,
            'day'       => $day,
            'month'     => $month,
            'dayOfWeek' => $dayOfWeek,
            'params'    => json_encode($params)
        ]);

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/cron',
                'message.cron.succesful.added'
            )
        );
    }

    /**
     * Edit the cron
     *
     * @param string $cron - Name of the Cron
     * @param integer $cronId
     * @param string $min
     * @param string $hour
     * @param string $day
     * @param string $month
     * @param string $dayOfWeek
     * @param array $params
     *
     * @throws QUI\Exception
     */
    public function edit(
        $cronId,
        $cron,
        $min,
        $hour,
        $day,
        $month,
        $dayOfWeek,
        $params = []
    ) {
        Permission::checkPermission('quiqqer.cron.edit');

        if (!$this->cronExists($cron)) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/cron', 'exception.cron.1002'),
                1002
            );
        }

        $cronData = $this->getCronData($cron);

        // test the cron data
        try {
            CronExpression::factory(
                "$min $hour $day $month $dayOfWeek"
            );
        } catch (\Exception $Exception) {
            throw new QUI\Exception($Exception->getMessage());
        }

        QUI::getDataBase()->update($this->table(), [
            'exec'      => $cronData['exec'],
            'title'     => $cronData['title'],
            'min'       => $min,
            'hour'      => $hour,
            'day'       => $day,
            'month'     => $month,
            'dayOfWeek' => $dayOfWeek,
            'params'    => json_encode($params)
        ], [
            'id' => $cronId
        ]);

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/cron',
                'message.cron.succesful.edit'
            )
        );
    }

    /**
     * activate a cron in the cron list
     *
     * @param integer $cronId - ID of the cron
     * @throws QUI\Permissions\Exception
     */
    public function activateCron($cronId)
    {
        Permission::checkPermission('quiqqer.cron.deactivate');

        QUI::getDataBase()->update(
            $this->table(),
            ['active' => 1],
            ['id' => (int)$cronId]
        );
    }

    /**
     * deactivate a cron in the cron list
     *
     * @param integer $cronId - ID of the cron
     * @throws QUI\Permissions\Exception
     */
    public function deactivateCron($cronId)
    {
        Permission::checkPermission('quiqqer.cron.activate');

        QUI::getDataBase()->update(
            $this->table(),
            ['active' => 0],
            ['id' => (int)$cronId]
        );
    }

    /**
     * Delete the crons
     *
     * @param array $ids - Array of the Cron-Ids
     * @throws QUI\Permissions\Exception
     */
    public function deleteCronIds($ids)
    {
        Permission::checkPermission('quiqqer.cron.delete');


        $DataBase = QUI::getDataBase();

        foreach ($ids as $id) {
            $id = (int)$id;

            if ($this->getCronById($id) === false) {
                return;
            }

            $DataBase->delete($this->table(), [
                'id' => $id
            ]);
        }
    }

    /**
     * Execute all upcoming crons
     *
     * @throws QUI\Permissions\Exception
     */
    public function execute()
    {
        Manager::log('Start cron execution (all crons)');

        // locking
        $lockKey = 'cron-execution';

        $Start                     = \date_create();
        self::$runtime['startAll'] = $Start->format('Y-m-d H:i:s');

        try {
            $Package = QUI::getPackage('quiqqer/cron');

            if (QUI\Lock\Locker::isLocked($Package, $lockKey, null, false)) {
                $time = QUI\Lock\Locker::getLockTime($Package, $lockKey);

                if ($time < 0) {
                    Manager::log(
                        'Crons cannot be executed because another instance is already executing crons.'
                    );

                    return;
                }
            }

            $lockTime = self::getLockTime(); // lock time in seconds
            $EndTime  = clone $Start;
            $EndTime  = $EndTime->add(\date_interval_create_from_date_string($lockTime.' seconds'));

            self::$runtime['lockEnd'] = $EndTime->format('Y-m-d H:i:s');

            QUI\Lock\Locker::lock($Package, $lockKey, $lockTime);
        } catch (\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
            QUI\System\Log::writeRecursive($Exception->getMessage());

            Manager::log(
                'Crons cannot be executed due to an error: '.$Exception->getMessage()
            );

            return;
        }

        Permission::checkPermission('quiqqer.cron.execute');

        $list = $this->getList();
        $time = time();

        $activeList = \array_filter($list, function ($entry) {
            return $entry['active'] == 1;
        });

        self::$runtime['total'] = \count($activeList);

        foreach ($activeList as $entry) {
            $lastexec = $entry['lastexec'];

            if (empty($lastexec)) {
                $lastexec = new \DateTime();
                $lastexec->setTimestamp(0);
            }

            $min       = $entry['min'];
            $hour      = $entry['hour'];
            $day       = $entry['day'];
            $month     = $entry['month'];
            $dayOfWeek = '*';

            if (isset($entry['dayOfWeek'])) {
                $dayOfWeek = $entry['dayOfWeek'];
            }

            try {
                $Cron = CronExpression::factory(
                    "$min $hour $day $month $dayOfWeek"
                );

                $next = $Cron->getNextRunDate($lastexec)->getTimestamp();
            } catch (\Exception $Exception) {
                QUI\System\Log::addError(
                    'Could not evaluate cron run date (Cron "'.$entry['title'].'" #'.$entry['id'].').'
                    .' Cron is deleted. Error :: '.$Exception->getMessage()
                );

                $this->deleteCronIds([$entry['id']]);

                continue;
            }

            // no execute
            if ($next > $time) {
                self::$runtime['finished']++;
                continue;
            }

            // execute cron
            try {
                self::$runtime['startCurrent']     = \date('Y-m-d H:i:s');
                self::$runtime['currentCronId']    = $entry['id'];
                self::$runtime['currentCronTitle'] = $entry['title'];

                $this->executeCron($entry['id']);

                self::$runtime['finished']++;

                $Now = \date_create();

                if ($Now > $EndTime) {
                    self::sendCronLockTimeoutNotification();
                }
            } catch (\Exception $Exception) {
                $message = print_r($entry, true);
                $message .= "\n".$Exception->getMessage();

                QUI\System\Log::addError($message);

                #self::log($message);
                QUI::getMessagesHandler()->addError($message);
            }
        }

        Manager::log('Finish cron execution (all crons)');

        try {
            QUI\Lock\Locker::unlock($Package, $lockKey);
        } catch (\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
        }
    }

    /**
     * Execute a cron
     *
     * @param integer $cronId - ID of the cron
     *
     * @return \QUI\Cron\Manager
     * @throws QUI\Exception
     */
    public function executeCron($cronId)
    {
        Permission::checkPermission('quiqqer.cron.execute');


        $cronData = $this->getCronById($cronId);
        $params   = [];

        if (!$cronData) {
            throw new QUI\Exception('Cron ID not exist');
        }

        if (isset($cronData['params'])) {
            $cronDataParams = json_decode($cronData['params'], true);

            if (is_array($cronDataParams)) {
                foreach ($cronDataParams as $entry) {
                    $params[$entry['name']] = $entry['value'];
                }
            }

            if (!is_array($params)) {
                $params = [];
            }
        }

        Manager::log('START cron "'.$cronData['title'].'" (ID: '.$cronId.')');
        $start    = \microtime(true);
        $starTime = \time();

        call_user_func_array($cronData['exec'], [$params, $this]);

        $end = \round(\microtime(true) - $start, 2);
        Manager::log('FINISH cron "'.$cronData['title'].'" (ID: '.$cronId.') - time: '.$end.' seconds');

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/cron',
                'message.cron.succesful.executed'
            )
        );

        QUI::getDataBase()->insert(self::tableHistory(), [
            'cronid'   => $cronId,
            'lastexec' => date('Y-m-d H:i:s', $starTime),
            'finish'   => date('Y-m-d H:i:s'),
            'uid'      => QUI::getUserBySession()->getId() ?: 0
        ]);


        QUI::getDataBase()->update(
            self::table(),
            ['lastexec' => date('Y-m-d H:i:s')],
            ['id' => $cronId]
        );

        return $this;
    }

    /**
     * Return the Crons which are available and from other Plugins provided
     *
     * @return array
     */
    public function getAvailableCrons()
    {
        $PackageManager = QUI::getPackageManager();
        $packageList    = $PackageManager->getInstalled();

        $result = [];

        foreach ($packageList as $entry) {
            $dir      = OPT_DIR.$entry['name'].'/';
            $cronFile = $dir.'cron.xml';

            if (!file_exists($cronFile)) {
                continue;
            }

            $result = array_merge(
                $result,
                $this->getCronsFromFile($cronFile)
            );
        }

        return $result;
    }

    /**
     * Return the data of a inserted cron
     *
     * @param integer $cronId - ID of the Cron
     *
     * @return array|false - Cron Data
     */
    public function getCronById($cronId)
    {
        $result = QUI::getDataBase()->fetch([
            'from'  => $this->table(),
            'where' => [
                'id' => (int)$cronId
            ],
            'limit' => 1
        ]);

        if (!isset($result[0])) {
            return false;
        }

        return $result[0];
    }

    /**
     * Return the data of a specific cron from the available cron list
     * This cron is not in the cron list
     *
     * @param string $cron - Cron-Identifier (package/package:NO) or name of the Cron or exec path of cron
     *
     * @return array|false - Cron Data
     */
    public function getCronData($cron)
    {
        $availableCrons = $this->getAvailableCrons();

        // cron by package Identifier package/package:NO
        $cronParts = explode(':', $cron);

        try {
            $Package  = QUI::getPackage($cronParts[0]);
            $cronFile = $Package->getXMLFilePath('cron.xml');

            if ($Package->isQuiqqerPackage()
                && $cronFile
                && isset($cronParts[1])
                && is_numeric($cronParts[1])
            ) {
                $cronNo   = (int)$cronParts[1];
                $cronList = $this->getCronsFromFile($cronFile);

                if (isset($cronList[$cronNo])) {
                    return $cronList[$cronNo];
                }
            }
        } catch (QUI\Exception $Exception) {
        }

        // search cron via title
        foreach ($availableCrons as $entry) {
            if ($entry['title'] == $cron || $entry['exec'] == $cron) {
                return $entry;
            }
        }

        return false;
    }

    /**
     * Return the history list
     *
     * @param array $params - select params -> (page, perPage)
     *
     * @return array
     */
    public function getHistoryList($params = [])
    {
        $limit = '0,20';
        $order = 'lastexec DESC';

        if (isset($params['perPage']) && isset($params['page'])) {
            $start = (int)$params['page'] - 1;
            $limit = $start.','.(int)$params['perPage'];
        }

        $data = QUI::getDataBase()->fetch([
            'from'  => self::tableHistory(),
            'limit' => $limit,
            'order' => $order
        ]);

        $dataOfCron = QUI::getDataBase()->fetch([
            'from' => $this->table()
        ]);

        $Users  = QUI::getUsers();
        $crons  = [];
        $result = [];

        // create assoc cron data array
        foreach ($dataOfCron as $cronData) {
            $crons[$cronData['id']] = $cronData;
        }

        $Nobody         = new QUI\Users\Nobody();
        $nobodyUsername = $Nobody->getUsername();

        foreach ($data as $entry) {
            $entry['cronTitle'] = '';
            $entry['username']  = '';

            if (isset($crons[$entry['cronid']])) {
                $entry['cronTitle'] = $crons[$entry['cronid']]['title'];
            }

            try {
                if (!empty($entry['uid'])) {
                    $username = $Users->get($entry['uid'])->getName();
                } else {
                    $username = $nobodyUsername;
                }

                $entry['username'] = $username;
            } catch (QUI\Exception $Exception) {
            }

            $result[] = $entry;
        }

        return $result;
    }

    /**
     * Return the history count, how many history entries exist
     *
     * @return integer
     */
    public function getHistoryCount()
    {
        $result = QUI::getDataBase()->fetch([
            'from'  => self::tableHistory(),
            'count' => 'id'
        ]);

        return $result[0]['id'];
    }

    /**
     * Return the cron list
     *
     * @return array
     */
    public function getList()
    {
        return QUI::getDataBase()->fetch([
            'from' => self::table()
        ]);
    }

    /**
     * Checks if a specific cron is already set up
     *
     * @param string $cron - cron title
     *
     * @return bool
     */
    public function isCronSetUp($cron)
    {
        $list = $this->getList();

        foreach ($list as $entry) {
            if ($entry['title'] == $cron) {
                return true;
            }
        }

        return false;
    }

    /**
     * Exist the cron?
     *
     * @param string $cron - name of the cron
     *
     * @return Bool
     */
    protected function cronExists($cron)
    {
        return $this->getCronData($cron) === false ? false : true;
    }

    /**
     * static
     */

    /**
     * Return the cron tabe
     *
     * @return string
     */
    public static function table()
    {
        return QUI_DB_PRFX.'cron';
    }

    /**
     * Return the cron tabe
     *
     * @return string
     */
    public static function tableHistory()
    {
        return QUI_DB_PRFX.'cron_history';
    }

    /**
     * Return the Crons from a XML File
     *
     * @param string $file
     *
     * @return array
     */
    public static function getCronsFromFile($file)
    {
        if (!file_exists($file)) {
            return [];
        }

        $Dom   = QUI\Utils\Text\XML::getDomFromXml($file);
        $crons = $Dom->getElementsByTagName('crons');

        if (!$crons || !$crons->length) {
            return [];
        }

        /* @var $Crons \DOMElement */
        $Crons = $crons->item(0);
        $list  = $Crons->getElementsByTagName('cron');

        if (!$list || !$list->length) {
            return [];
        }

        $result = [];

        for ($i = 0; $i < $list->length; $i++) {
            $Cron = $list->item($i);

            $title  = '';
            $desc   = '';
            $params = [];

            /* @var $Cron \DOMElement */
            $Title  = $Cron->getElementsByTagName('title');
            $Desc   = $Cron->getElementsByTagName('description');
            $Params = $Cron->getElementsByTagName('param');

            if ($Title->length) {
                $title = QUI\Utils\DOM::getTextFromNode($Title->item(0));
            }

            if ($Desc->length) {
                $desc = QUI\Utils\DOM::getTextFromNode($Desc->item(0));
            }

            if ($Params->length) {
                foreach ($Params as $k => $Param) {
                    /* @var $Param \DOMElement */
                    $param = [
                        'name'     => $Param->getAttribute('name'),
                        'type'     => $Param->getAttribute('type'),
                        'data-qui' => $Param->getAttribute('data-qui'),
                        'desc'     => false
                    ];

                    if ($Param->childNodes->length) {
                        $param['desc'] = QUI\Utils\DOM::getTextFromNode($Param);
                    }

                    $params[] = $param;
                }
            }

            $result[] = [
                'title'       => $title,
                'description' => $desc,
                'exec'        => $Cron->getAttribute('exec'),
                'params'      => $params
            ];
        }

        return $result;
    }

    /**
     * Print a message to the log cron.log
     *
     * @param String $message - Message
     */
    public static function log($message)
    {
        if (self::isWriteCronLog()) {
            QUI\System\Log::addInfo($message, [], 'cron');
        }
    }

    /**
     * Write cron log?
     *
     * @return bool
     */
    protected static function isWriteCronLog()
    {
        if (!\is_null(self::$writeCronLog)) {
            return self::$writeCronLog;
        }

        try {
            self::$writeCronLog = \boolval(
                QUI::getPackage('quiqqer/cron')->getConfig()->get(
                    'settings',
                    'writeCronLog'
                )
            );
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            self::$writeCronLog = false;
        }

        return self::$writeCronLog;
    }

    /**
     * Send admin notification when cron lock time is exceeded.
     *
     * @return void
     */
    protected static function sendCronLockTimeoutNotification()
    {
        // Check if notification shall be sent
        if (self::$lockTimeoutNotificationSent) {
            return;
        }

        try {
            $Conf = QUI::getPackage('quiqqer/cron')->getConfig();

            if (empty($Conf->get('settings', 'cron_lock_timeout_notification'))) {
                return;
            }
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            return;
        }

        $adminMail = QUI::conf('mail', 'admin_mail');

        if (empty($adminMail)) {
            QUI\System\Log::addWarning(
                'quiqqer/cron -> Cannot send lock timeout notification since no administrator e-mail is configured in'
                .' this QUIQQER system.'
            );

            return;
        }

        try {
            $Mailer = new QUI\Mail\Mailer();
            $Mailer->addRecipient($adminMail);

            $L = QUI::getLocale();

            $Mailer->setSubject(
                $L->get('quiqqer/cron', 'notification.lock_timeout.subject')
            );

            $End = \date_create(self::$runtime['lockEnd']);
            $Now = \date_create();

            $TimeDiff = $End->diff($Now);

            $Mailer->setBody(
                $L->get('quiqqer/cron', 'notification.lock_timeout.body', \array_merge(self::$runtime, [
                    'host' => QUI::conf('globals', 'host'),
                    'diff' => $TimeDiff->format('%H:%M:%S')
                ]))
            );

            $Mailer->send();

            self::$lockTimeoutNotificationSent = true;
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }
    }

    /**
     * Get cron lock time
     *
     * @return int - Lock time (seconds)
     */
    protected static function getLockTime()
    {
        try {
            $Conf     = QUI::getPackage('quiqqer/cron')->getConfig();
            $lockTime = $Conf->get('settings', 'cron_lock_time');

            if (empty($lockTime)) {
                return 1440;
            }

            return $lockTime;
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            return 1440;
        }
    }
}
