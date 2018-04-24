<?php
/*
Plugin Name: LogHero Client
Version: 0.0.1
*/

namespace LogHero\Wordpress;

use LogHero\Client\FileLogBuffer;

if ( !class_exists( 'LogHeroClient_Plugin' ) ) {
    require_once(dirname(__FILE__) . '/sdk/src/LogHero.php');
    require_once(dirname(__FILE__) . '/sdk/src/LogBuffer.php');

    # TODO Handle proxies HTTP_X_FORWARDED_FOR (see cerber)
    class LogHeroLogEventFactory {
        private $ipAddressKeys = array(
            'REMOTE_ADDR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'SERVER_ADDR'
        );

        public function create() {
            $logEvent = new \LogHero\Client\LogEvent();
            $this
                ->setHostname($logEvent)
                ->setLandingPagePath($logEvent)
                ->setUserAgent($logEvent)
                ->setIpAddress($logEvent)
                ->setTimestampAndPageLoadTime($logEvent)
                ->setMethod($logEvent)
                ->setStatusCode($logEvent);
            return $logEvent;
        }

        private function setHostname($logEvent) {
            $logEvent->setHostname($_SERVER['HTTP_HOST']);
            return $this;
        }

        private function setLandingPagePath($logEvent) {
            $logEvent->setLandingPagePath($_SERVER['REQUEST_URI']);
            return $this;
        }

        private function setMethod($logEvent) {
            $logEvent->setMethod(preg_replace('/[^\w]/', '', $_SERVER['REQUEST_METHOD']));
            return $this;
        }

        private function setStatusCode($logEvent) {
            if ( function_exists( 'http_response_code' ) ) {
                $logEvent->setStatusCode(http_response_code());
            }
            return $this;
        }

        private function setUserAgent($logEvent) {
            if (!empty($_SERVER['HTTP_USER_AGENT'])) {
                $ua = $_SERVER['HTTP_USER_AGENT'];
                $logEvent->setUserAgent($ua);
            }
            return $this;
        }

        private function setTimestampAndPageLoadTime($logEvent) {
            $unixTimestamp = null;
            $pageLoadTimeMilliSec = null;
            if (!empty($_SERVER['REQUEST_TIME_FLOAT'])) { // PHP >= 5.4
                $unixTimestamp = $_SERVER['REQUEST_TIME_FLOAT'];
                $pageLoadTimeMilliSec = (int) (1000 * (microtime(true) - $unixTimestamp));
                $logEvent->setPageLoadTimeMilliSec($pageLoadTimeMilliSec);
            }
            else {
                $unixTimestamp = microtime(true);
            }
            $timeStamp = new \DateTime();
            $timeStamp->setTimestamp($unixTimestamp);
            $logEvent->setTimestamp($timeStamp);
            return $this;
        }

        private function setIpAddress($logEvent) {
            $ipAddress = null;
            foreach ($this->ipAddressKeys as $key) {
                $ipAddress = filter_var($_SERVER[$key], FILTER_VALIDATE_IP);
                if ($ipAddress) {
                    break;
                }
            }
            $logEvent->setIpAddress($ipAddress);
            return $this;
        }
    }

    class LogHeroClient_Plugin {
        protected static $Instance = false;
        protected $apiKey;
        protected $apiClient;
        protected $logEventFactory;
        protected $clientId = 'Wordpress Plugin loghero/wp@0.1.0';

        public function __construct() {
            $this->apiKey = get_option('api_key');
            $this->logEventFactory = new LogHeroLogEventFactory();
            $this->apiClient = \LogHero\Client\Client::create(
                $this->apiKey,
                $this->clientId,
                new FileLogBuffer(__DIR__ . '/logs/buffer.loghero.io.txt')
            );
            add_action('shutdown', array($this, 'sendLogEvent'));
        }

        public static function getInstance() {
            if (!self::$Instance) {
                self::$Instance = new self();
            }
            return self::$Instance;
        }

        public function sendLogEvent() {
            $logEvent = $this->logEventFactory->create();
            $this->apiClient->submit($logEvent);
        }

    }

    $LogHeroClientPlugin = LogHeroClient_Plugin::getInstance();

    if (is_admin()) {
        require_once(dirname(__FILE__) . '/admin/loghero-admin.php');
    }
}
