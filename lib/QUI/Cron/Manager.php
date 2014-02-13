<?php

/**
 * This File contains QUI\Cron\Manager
 */

namespace QUI\Cron;

/**
 * Cron Manager
 *
 * @author www.pcsg.de (Henning Leutz)
 */

class Manager
{
    /**
     * Add a cron
     *
     * @param String $cron - Name of the Cron
     * @param String $min - On which minute should it start
     * @param String $hour - On which hour should it start
     * @param String $day - On which day should it start
     * @param String $month - On which month should it start
     * @param Array $params - Cron Parameter
     */
    public function add($cron, $min, $hour, $day, $month, $params=array())
    {
        if ( !$this->_cronExists( $cron ) ) {
            throw new \QUI\Exception( 'Cannot add Cron. Cron not exists', 404 );
        }

        $cronData = $this->getCronData( $cron );

        if ( !is_array( $params ) ) {
            $params = array();
        }

        \QUI::getDataBase()->insert($this->Table(), array(
            'active' => 1,
            'exec'   => $cronData['exec'],
            'title'  => $cronData['title'],
            'min'    => $min,
            'hour'   => $hour,
            'day'    => $day,
            'month'  => $month,
            'params' => json_encode( $params )
        ));

        \QUI::getMessagesHandler()->addSuccess(
            'Cron erfolgreich hinzugefügt'
        );
    }

    /**
     * Edit the cron
     *
     * @param unknown $cronId
     * @param unknown $min
     * @param unknown $hour
     * @param unknown $day
     * @param unknown $month
     * @param unknown $params
     */
    public function edit($cronId, $min, $hour, $day, $month, $params=array())
    {
        \QUI::getDataBase()->update($this->Table(), array(
            'min'    => $min,
            'hour'   => $hour,
            'day'    => $day,
            'month'  => $month,
            'params' => json_encode( $params )
        ), array(
            'id' => $cronId
        ));


        \QUI::getMessagesHandler()->addSuccess(
            'Cron erfolgreich editiert'
        );
    }

    /**
     * activate a cron in the cron list
     * @param Integer $cronId - ID of the cron
     */
    public function activateCron($cronId)
    {
        \QUI::getDataBase()->update(
            $this->Table(),
            array('active' => 1),
            array('id' => (int)$cronId)
        );
    }

    /**
     * deactivate a cron in the cron list
     * @param Integer $cronId - ID of the cron
     */
    public function deactivateCron($cronId)
    {
        \QUI::getDataBase()->update(
            $this->Table(),
            array('active' => 0),
            array('id' => (int)$cronId)
        );
    }

    /**
     * Delete the crons
     * @param Array $ids - Array of the Cron-Ids
     */
    public function deleteCronIds($ids)
    {
        $DataBase = \QUI::getDataBase();

        foreach ( $ids as $id )
        {
            $id = (int)$id;

            if ( $this->getCronById( $id ) === false ) {
                return;
            }

            $DataBase->delete($this->Table(), array(
                'id' => $id
            ));
        }
    }

    /**
     * Execute all upcoming crons
     */
    public function execute()
    {
        $list = $this->getList();
        $time = time();


        foreach ( $list as $entry )
        {
            if ( $entry['active'] != 1 ) {
                continue;
            }

            $lastexec = $entry['lastexec'];

            $min   = $entry['min'];
            $hour  = $entry['hour'];
            $day   = $entry['day'];
            $month = $entry['month'];
            $year  = '*';

            $Cron = \Cron\CronExpression::factory(
                "$min $hour $day $month $year"
            );

            $next = $Cron->getNextRunDate( $lastexec )->getTimestamp();

            // no execute
            if ( $next > $time ) {
                continue;
            }

            // execute cron
            $this->executeCron( $entry['id'] );
        }
    }

    /**
     * Execute a cron
     *
     * @param Integer $cronId - ID of the cron
     * @return \QUI\Cron\Manager
     */
    public function executeCron($cronId)
    {
        $cronData = $this->getCronById( $cronId );

        if ( !$cronData ) {
            throw new \QUI\Exception( 'Cron ID not exist' );
        }

        call_user_func_array( $cronData['exec'], array($this) );

        \QUI::getMessagesHandler()->addSuccess(
            \QUI::getLocale()->get(
                'quiqqer/cron',
                'message.cron.succesful.executed'
            )
        );

        \QUI::getDataBase()->insert(self::TableHistory(), array(
            'cronid'   => $cronId,
            'lastexec' => date( 'Y-m-d H:i:s' ),
            'uid'      => \QUI::getUserBySession()->getId()
        ));


        \QUI::getDataBase()->update(
            self::Table(),
            array('lastexec' => date( 'Y-m-d H:i:s' )),
            array('id' => $cronId)
        );

        return $this;
    }

    /**
     * Return the Crons which are available and from other Plugins provided
     *
     * @return Array
     */
    public function getAvailableCrons()
    {
        $PackageManager = \QUI::getPackageManager();
        $packageList    = $PackageManager->getInstalled();

        $result = array();

        foreach ( $packageList as $entry )
        {
            $dir      = OPT_DIR . $entry['name'] .'/';
            $cronFile = $dir . 'cron.xml';

            if ( !file_exists( $cronFile ) ) {
                continue;
            }

            $result = array_merge(
                $result,
                $this->getCronsFromFile( $cronFile )
            );
        }

        return $result;
    }

    /**
     * Return the data of a inserted cron
     *
     * @param Integer $cronId - ID of the Cron
     * @return Array|false - Cron Data
     */
    public function getCronById($cronId)
    {
        $result = \QUI::getDataBase()->fetch(array(
            'from'  => $this->Table(),
            'where' => array(
                'id' => (int)$cronId
            ),
            'limit' => 1
        ));

        if ( !isset( $result[ 0 ] ) ) {
            return false;
        }

        return $result[ 0 ];
    }

    /**
     * Return the data of a specific cron from the available cron list
     * This cron is not in the cron list
     *
     * @param String $cron - Name of the Cron
     * @return Array|false - Cron Data
     */
    public function getCronData($cron)
    {
        $availableCrons = $this->getAvailableCrons();

        // check if cron is available
        foreach ( $availableCrons as $entry )
        {
            if ( $entry['title'] == $cron ) {
                return $entry;
            }
        }

        return false;
    }

    /**
     * Return the history list
     */
    public function getHistoryList()
    {
        return \QUI::getDataBase()->fetch(array(
            'from' => self::TableHistory()
        ));
    }

    /**
     * Return the cron list
     *
     * @return Array
     */
    public function getList()
    {
        return \QUI::getDataBase()->fetch(array(
            'from' => self::Table()
        ));
    }

    /**
     * Exist the cron?
     *
     * @return Bool
     */
    protected function _cronExists($cron)
    {
        return $this->getCronData( $cron ) === false ? false : true;
    }

    /**
     * static
     */

    /**
     * Return the cron tabe
     *
     * @return String
     */
    static function Table()
    {
        return QUI_DB_PRFX .'cron';
    }

    /**
     * Return the cron tabe
     *
     * @return String
     */
    static function TableHistory()
    {
        return QUI_DB_PRFX .'cron_history';
    }

    /**
     * Return the Crons from a XML File
     *
     * @param String $file
     * @return Array
     */
    static function getCronsFromFile($file)
    {
        if ( !file_exists( $file ) ) {
            return array();
        }

        $Dom   = \QUI\Utils\XML::getDomFromXml( $file );
        $crons = $Dom->getElementsByTagName( 'crons' );

        if ( !$crons || !$crons->length ) {
            return array();
        }

        $Crons = $crons->item( 0 );
        $list  = $Crons->getElementsByTagName( 'cron' );

        if ( !$list || !$list->length ) {
            return array();
        }

        $result = array();

        for ( $i = 0; $i < $list->length; $i++ )
        {
            $Cron = $list->item( $i );

            $title = '';
            $desc  = '';

            $Title = $Cron->getElementsByTagName( 'title' );
            $Desc  = $Cron->getElementsByTagName( 'description' );

            if ( $Title->length ) {
                $title = trim( $Title->item( 0 )->nodeValue );
            }

            if ( $Desc->length ) {
                $desc = trim( $Desc->item( 0 )->nodeValue );
            }

            $result[] = array(
                'title'       => $title,
                'description' => $desc,
                'exec'        => $Cron->getAttribute( 'exec' )
            );
        }

        return $result;
    }

    /**
     * Print a message to the log cron.log
     *
     * @param String $message - Message
     */
    static function log($message)
    {
        $User = \QUI::getUsers()->getUserBySession();

        $dir  = VAR_DIR . 'log/';
        $file = $dir . 'cron_'. date('Y-m-d') .'.log';

        $str = '['. date('Y-m-d H:i:s') .' :: '. $User->getName() .'] '. $message;

        QUI\System\Log::write( $str, 'cron' );
    }
}
