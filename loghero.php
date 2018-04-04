<?php
/*
Plugin Name: LogHero Client
Version: 0.0.1
*/

if ( !class_exists( 'LogHeroClient_Plugin' ) ) {
    require_once(dirname(__FILE__) . '/sdk/src/LogHero.php');

    # TODO Handle proxies HTTP_X_FORWARDED_FOR (see cerber)
    class LogHeroLogEventFactory {
        private $ipAddressKeys = array(
            'REMOTE_ADDR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'SERVER_ADDR'
        );

        public function create() {
            $logEvent = new LHLogEvent();
            $this->setHostname($logEvent);
            $this->setLandingPagePath($logEvent);
            $this->setUserAgent($logEvent);
            $this->setIpAddress($logEvent);
            $this->setTimestamp($logEvent);
            $this->setMethod($logEvent);
            $this->setStatusCode($logEvent);
            return $logEvent;
        }

        private function setHostname($logEvent) {
            $logEvent->setHostname($_SERVER['HTTP_HOST']);
        }

        private function setLandingPagePath($logEvent) {
            $logEvent->setUserAgent($_SERVER['REQUEST_URI']);
        }

        private function setMethod($logEvent) {
            $logEvent->setMethod(preg_replace('/[^\w]/', '', $_SERVER['REQUEST_METHOD']));
        }

        private function setStatusCode($logEvent) {
            if ( function_exists( 'http_response_code' ) ) {
                $logEvent->setStatusCode(http_response_code());
            }
        }

        private function setUserAgent($logEvent) {
            if (!empty($_SERVER['HTTP_USER_AGENT'])) {
                $ua = $_SERVER['HTTP_USER_AGENT'];
                $logEvent->setUserAgent($ua);
            }
        }

        private function setTimestamp($logEvent) {
            $unixTimestamp = null;
            if (!empty( $_SERVER['REQUEST_TIME_FLOAT'])) { // PHP >= 5.4
                $unixTimestamp = $_SERVER['REQUEST_TIME_FLOAT'];
            }
            else {
                $unixTimestamp = microtime( true );
            }
            $timeStamp = new DateTime();
            $timeStamp->setTimestamp($unixTimestamp);
            $logEvent->setTimestamp($timeStamp);
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
        }
    }

    class LogHeroClient_Plugin {
        private static $Instance = false;
        private $apiClient;
        private $logEventFactory;

        public function __construct() {
            $apiKey = get_option('api_key');
            $this->logEventFactory = new LogHeroLogEventFactory();
            $this->apiClient = new LHClient($apiKey, 3);
            add_action('shutdown', array($this, 'sendLogEvent'));
        }

        public static function getInstance() {
            if (!self::$Instance) {
                self::$Instance = new self;
            }
            return self::$Instance;
        }

        public function sendLogEvent() {
            $logEvent = $this->logEventFactory->create();
            $this->apiClient->submit($logEvent);
            $this->apiClient->flush();
        }
    }

    $LogHeroClientPlugin = LogHeroClient_Plugin::getInstance();

    if (is_admin()) {
        require_once(dirname(__FILE__) . '/admin/loghero-admin.php');
    }
}
