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
    require_once(dirname(__FILE__) . '/sdk/src/LogEventFactory.php');

    class LogHeroClient_Plugin {
        protected static $Instance = false;
        protected $apiKey;
        protected $apiClient;
        protected $logEventFactory;
        protected $clientId = 'Wordpress Plugin loghero/wp@0.1.0';

        public function __construct() {
            $this->apiKey = get_option('api_key');
            $this->logEventFactory = new \LogHero\Client\LogEventFactory();
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

    LogHeroClient_Plugin::getInstance();

    if (is_admin()) {
        require_once(dirname(__FILE__) . '/admin/loghero-admin.php');
    }
}
