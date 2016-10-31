<?php

namespace QUI\Cron;

use QUI;
use QUI\System\Log;

class CronService
{

    const CRONSERVICE_URL = "http://server.local";

    private $domain;
    private $https;
    private $packageDir;


    public function __construct()
    {
        $host    = QUI::$Conf->get("globals", "host");
        $cms_dir = QUI::$Conf->get("globals", "cms_dir");
        $opt_dir = QUI::$Conf->get("globals", "opt_dir");
        $url_dir = QUI::$Conf->get("globals", "url_dir");

        // Parse Domain and protocol
        if (strpos($host, "https://") !== false) {
            $this->https  = true;
            $this->domain = str_replace("https://", "", $host);
        } elseif (strpos($host, "http://") !== false) {
            $this->https  = false;
            $this->domain = str_replace("http://", "", $host);
        } else {
            $this->https  = false;
            $this->domain = $host;
        }

        // Parse Package dir
        $this->packageDir = $url_dir . str_replace($cms_dir, "", $opt_dir);
    }

    /**
     * Will register this quiqqer instance.
     *
     * @param $email - Email used for communication. Must be valid.
     */
    public function register($email)
    {
        $this->sendRegistrationRequest($this->domain, $email, $this->packageDir, $this->https);
    }

    /**
     * Gets the status of the given domain.
     *
     * Returnformat :
     * array(
     *       'status'           => 0,  (0=unregistered; 1=active; 2=inactive)
     *       'current_failures' => int,
     *       'total_failures'   => int,
     *       'last_execution'   => string (mysql dateformat)
     * @return mixed
     */
    public function getStatus()
    {
        $status = $this->makeServerAjaxCall('package_pcsg_cronservice_ajax_getStatus', array(
            'domain' => $this->domain
        ));

        return $status;
    }

    public function revokeRegistration()
    {
        $token = "0VamlwcIlNUgE79ocOgpUKTvjhS8I4cr";

        $this->makeServerAjaxCall('package_pcsg_cronservice_ajax_revokeRegistration', array(
            'domain' => $this->domain,
            'token'  => $token
        ));
    }

    /**
     * Sends an ajax request to the cronservice server.
     *
     * @param $domain - The domain to be registered. Example : example.org
     * @param $email - The Email that should be used for communication.
     * @param $packageDir - The package url dir
     * @param $https - wether or not http secure should be used to call the cron.php
     */
    private function sendRegistrationRequest($domain, $email, $packageDir, $https)
    {
        $url = self::CRONSERVICE_URL . "/admin/ajax.php?" .
            "_rf=" . urlencode("[\"package_pcsg_cronservice_ajax_register\"]") .
            "&package=" . urlencode("pcsg/cronservice") .
            "&lang=de" . // TODO Detect language
            "&domain=" . urlencode($domain) .
            "&email=" . urlencode($email) .
            "&packageDir=" . urlencode($packageDir) .
            "&https=" . ($https ? "1" : "0");

        Log::addDebug($url);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL            => $url,
            CURLOPT_USERAGENT      => 'QUIQQER'
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        Log::addDebug($response);
    }

    /**
     * Calls the given ajax function on the Cronservice server and returns its output
     * @param $function - Ajax function name
     * @param $params - Params to pass
     * @return mixed
     * @throws QUI\Exception
     */
    private function makeServerAjaxCall($function, $params)
    {
        $url = self::CRONSERVICE_URL . "/admin/ajax.php?" .
            "_rf=" . urlencode('["' . $function . '"]') .
            "&package=" . urlencode("pcsg/cronservice") .
            "&lang=de";// TODO Detect language

        foreach ($params as $param => $value) {
            $url .= '&' . $param . '=' . urlencode($value);
        }

        Log::addDebug("Ajax Request : " . $url);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL            => $url,
            CURLOPT_USERAGENT      => 'QUIQQER'
        ));

        $response = curl_exec($curl);

        Log::addDebug($response);

        curl_close($curl);

        // Process raw ajax response
        $response = substr($response, 9, -10);
        $response = json_decode($response, true);


        if (isset($response[$function]['Exception'])) {
            throw new QUI\Exception($response[$function]['Exception']['message']);
        }

        Log::writeRecursive($response);

        return $response[$function]['result'];
    }

}