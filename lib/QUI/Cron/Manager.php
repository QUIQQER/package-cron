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
     * @param unknown $cron - Name of the Cron
     * @param unknown $min - On which minute should it start
     * @param unknown $hour - On which hour should it start
     * @param unknown $day - On which day should it start
     * @param unknown $month - On which month should it start
     */
    public function add($cron, $min, $hour, $day, $month)
    {
        if ( !$this->_cronExists( $cron ) ) {
            throw new \QUI\Exception( 'Cannot add Cron. Cron not exists', 404 );
        }

        $cronData = $this->getCronData( $cron );

        \QUI::getDataBase()->insert($this->Table(), array(
            'exec'   => $cronData['exec'],
            'title'  => $cronData['title'],
            'min'    => $min,
            'hour'   => $hour,
            'day'    => $day,
            'month'  => $month
        ));

        \QUI::getMessagesHandler()->addSuccess(
            'Cron erfolgreich hinzugefügt'
        );
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
     * Return the data of a specific cron
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
