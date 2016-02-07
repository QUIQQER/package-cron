<?php

/**
 * External execution
 */


define('QUIQQER_SYSTEM', true);
require dirname(dirname(dirname(dirname(__FILE__)))) . "/header.php";

$Cron = new \QUI\Cron\Manager();
$Cron->execute();
