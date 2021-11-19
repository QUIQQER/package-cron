<?php

/**
 * External execution
 */

define('QUIQQER_SYSTEM', true);
define('SYSTEM_INTERN', true); // Session user = System user

require dirname(dirname(dirname(dirname(__FILE__))))."/header.php";

use \Symfony\Component\HttpFoundation\Response;

// Vars löschen die Probleme bereiten können
$_REQUEST = [];
$_POST    = [];
$_GET     = [];

$Cron     = new QUI\Cron\Manager();
$Response = QUI::getGlobalResponse();

QUI\Permissions\Permission::setUser(QUI::getUsers()->getSystemUser());

try {
    $Cron->execute();

    $Response->setStatusCode(Response::HTTP_OK);
    $Response->send();
} catch (QUI\Exception $Exception) {
    QUI\System\Log::addAlert($Exception->getMessage(), [
        'type' => 'cron execution'
    ]);

    $Response->setStatusCode(Response::HTTP_SERVICE_UNAVAILABLE);
    $Response->send();
}

exit;
