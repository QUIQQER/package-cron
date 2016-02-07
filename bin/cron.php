<?php

/**
 * External execution
 */

define('QUIQQER_SYSTEM', true);
require dirname(dirname(dirname(dirname(__FILE__)))) . "/header.php";

use \Symfony\Component\HttpFoundation\Response;

$Cron     = new QUI\Cron\Manager();
$Response = QUI::getGlobalResponse();

try {
    $Cron->execute();

    $Response->setStatusCode(Response::HTTP_OK);
    $Response->send();

} catch (QUI\Exception $Exception) {
    $Response->setStatusCode(Response::HTTP_SERVICE_UNAVAILABLE);
    $Response->send();
}
