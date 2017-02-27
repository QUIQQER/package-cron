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
     * Add a cron
     *
     * @param string $cron - Name of the Cron
     * @param string $min - On which minute should it start
     * @param string $hour - On which hour should it start
     * @param string $day - On which day should it start
     * @param string $month - On which month should it start
     * @param string $dayOfWeek - day of week (0 - 6) (0 to 6 are Sunday to Saturday,
     *        or use names; 7 is Sunday, the same as 0)
     * @param array $params - Cron Parameter
     *
     * @throws QUI\Exception
     */
    public function add($cron, $min, $hour, $day, $month, $dayOfWeek, $params = array())
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
            $params = array();
        }

        QUI::getDataBase()->insert($this->table(), array(
            'active'    => 1,
            'exec'      => $cronData['exec'],
            'title'     => $cronData['title'],
            'min'       => $min,
            'hour'      => $hour,
            'day'       => $day,
            'month'     => $month,
            'dayOfWeek' => $dayOfWeek,
            'params'    => json_encode($params)
        ));

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
        $params = array()
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

        QUI::getDataBase()->update($this->table(), array(
            'exec'      => $cronData['exec'],
            'title'     => $cronData['title'],
            'min'       => $min,
            'hour'      => $hour,
            'day'       => $day,
            'month'     => $month,
            'dayOfWeek' => $dayOfWeek,
            'params'    => json_encode($params)
        ), array(
            'id' => $cronId
        ));

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
     */
    public function activateCron($cronId)
    {
        Permission::checkPermission('quiqqer.cron.deactivate');

        QUI::getDataBase()->update(
            $this->table(),
            array('active' => 1),
            array('id' => (int)$cronId)
        );
    }

    /**
     * deactivate a cron in the cron list
     *
     * @param integer $cronId - ID of the cron
     */
    public function deactivateCron($cronId)
    {
        Permission::checkPermission('quiqqer.cron.activate');

        QUI::getDataBase()->update(
            $this->table(),
            array('active' => 0),
            array('id' => (int)$cronId)
        );
    }

    /**
     * Delete the crons
     *
     * @param array $ids - Array of the Cron-Ids
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

            $DataBase->delete($this->table(), array(
                'id' => $id
            ));
        }
    }

    /**
     * Execute all upcoming crons
     */
    public function execute()
    {
        Permission::checkPermission('quiqqer.cron.execute');


        $list = $this->getList();
        $time = time();

        foreach ($list as $entry) {
            if ($entry['active'] != 1) {
                continue;
            }

            $lastexec = $entry['lastexec'];

            $min       = $entry['min'];
            $hour      = $entry['hour'];
            $day       = $entry['day'];
            $month     = $entry['month'];
            $dayOfWeek = '*';

            if (isset($entry['dayOfWeek']) && !empty($entry['dayOfWeek'])) {
                $dayOfWeek = $entry['dayOfWeek'];
            }


            $Cron = CronExpression::factory(
                "$min $hour $day $month $dayOfWeek"
            );

            $next = $Cron->getNextRunDate($lastexec)->getTimestamp();

            // no execute
            if ($next > $time) {
                continue;
            }

            // execute cron
            try {
                $this->executeCron($entry['id']);
            } catch (\Exception $Exception) {
                $message = print_r($entry, true);
                $message .= "\n" . $Exception->getMessage();

                self::log($message);
                QUI::getMessagesHandler()->addError($message);
            }
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
        $params   = array();

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
                $params = array();
            }
        }

        call_user_func_array($cronData['exec'], array($params, $this));

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/cron',
                'message.cron.succesful.executed'
            )
        );

        QUI::getDataBase()->insert(self::tableHistory(), array(
            'cronid'   => $cronId,
            'lastexec' => date('Y-m-d H:i:s'),
            'uid'      => QUI::getUserBySession()->getId()
        ));


        QUI::getDataBase()->update(
            self::table(),
            array('lastexec' => date('Y-m-d H:i:s')),
            array('id' => $cronId)
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

        $result = array();

        foreach ($packageList as $entry) {
            $dir      = OPT_DIR . $entry['name'] . '/';
            $cronFile = $dir . 'cron.xml';

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
        $result = QUI::getDataBase()->fetch(array(
            'from'  => $this->table(),
            'where' => array(
                'id' => (int)$cronId
            ),
            'limit' => 1
        ));

        if (!isset($result[0])) {
            return false;
        }

        return $result[0];
    }

    /**
     * Return the data of a specific cron from the available cron list
     * This cron is not in the cron list
     *
     * @param string $cron - Name of the Cron
     *
     * @return array|false - Cron Data
     */
    public function getCronData($cron)
    {
        $availableCrons = $this->getAvailableCrons();

        // check if cron is available
        foreach ($availableCrons as $entry) {
            if ($entry['title'] == $cron) {
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
    public function getHistoryList($params = array())
    {
        $limit = '0,20';
        $order = 'lastexec DESC';

        if (isset($params['perPage']) && isset($params['page'])) {
            $start = (int)$params['page'] - 1;
            $limit = $start . ',' . (int)$params['perPage'];
        }

        $data = QUI::getDataBase()->fetch(array(
            'from'  => self::tableHistory(),
            'limit' => $limit,
            'order' => $order
        ));

        $dataOfCron = QUI::getDataBase()->fetch(array(
            'from' => $this->table()
        ));

        $Users  = QUI::getUsers();
        $crons  = array();
        $result = array();

        // create assoc cron data array
        foreach ($dataOfCron as $cronData) {
            $crons[$cronData['id']] = $cronData;
        }

        foreach ($data as $entry) {
            $entry['cronTitle'] = '';
            $entry['username']  = '';

            if (isset($crons[$entry['cronid']])) {
                $entry['cronTitle'] = $crons[$entry['cronid']]['title'];
            }

            try {
                $entry['username'] = $Users->get($entry['uid'])->getName();
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
        $result = QUI::getDataBase()->fetch(array(
            'from'  => self::tableHistory(),
            'count' => 'id'
        ));

        return $result[0]['id'];
    }

    /**
     * Return the cron list
     *
     * @return array
     */
    public function getList()
    {
        return QUI::getDataBase()->fetch(array(
            'from' => self::table()
        ));
    }

    /**
     * Checks if a specific cron is already set up
     *
     * @param string $cron - cron title
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
        return QUI_DB_PRFX . 'cron';
    }

    /**
     * Return the cron tabe
     *
     * @return string
     */
    public static function tableHistory()
    {
        return QUI_DB_PRFX . 'cron_history';
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
            return array();
        }

        $Dom   = QUI\Utils\Text\XML::getDomFromXml($file);
        $crons = $Dom->getElementsByTagName('crons');

        if (!$crons || !$crons->length) {
            return array();
        }

        /* @var $Crons \DOMElement */
        $Crons = $crons->item(0);
        $list  = $Crons->getElementsByTagName('cron');

        if (!$list || !$list->length) {
            return array();
        }

        $result = array();

        for ($i = 0; $i < $list->length; $i++) {
            $Cron = $list->item($i);

            $title  = '';
            $desc   = '';
            $params = array();

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
                foreach ($Params as $Param) {
                    /* @var $Param \DOMElement */
                    $params[] = array(
                        'name' => $Param->getAttribute('name'),
                        'type' => $Param->getAttribute('type')
                    );
                }
            }

            $result[] = array(
                'title'       => $title,
                'description' => $desc,
                'exec'        => $Cron->getAttribute('exec'),
                'params'      => $params
            );
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
        QUI\System\Log::addInfo($message, array(), 'cron');
    }
}
