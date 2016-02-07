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
    if (!QUI::getUserBySession()->getId()
        && isset($_GET['username'])
        && isset($_GET['password'])
        && isset($_GET['login'])
    ) {
        $User = QUI::getUsers()->login(
            $_GET['username'],
            $_GET['password']
        );

        if ($User->getId()) {
            QUI::getSession()->set('uid', $User->getId());

            QUI\Rights\Permission::setUser($User);
        }
    }
} catch (QUI\Exception $Exception) {
    $Response->setStatusCode(Response::HTTP_FORBIDDEN);
    $Response->send();
}

try {
    $Cron->execute();

    $Response->setStatusCode(Response::HTTP_OK);
    $Response->send();

} catch (QUI\Exception $Exception) {
    QUI\System\Log::addAlert($Exception->getMessage());

    $Response->setStatusCode(Response::HTTP_SERVICE_UNAVAILABLE);
    $Response->send();
}

exit;
