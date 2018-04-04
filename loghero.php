<?php
/*
Plugin Name: LogHero Client
Version: 0.0.1
*/

if ( !class_exists( 'LogHeroClient_Plugin' ) ) {

    class LogHeroClient_Plugin {
        private static $Instance = false;
        private $apiKey;

        public function __construct() {
            $this->apiKey = get_option('api_key');
            add_action('shutdown', array($this, 'sendLogEvent'));
        }

        public static function getInstance() {
            if (!self::$Instance) {
                self::$Instance = new self;
            }
            return self::$Instance;
        }

        public function sendLogEvent() {
            echo '<p>SENDING LOG EVENT WITH API KEY '.$this->apiKey.'</p>';
        }

    }

    $LogHeroClientPlugin = LogHeroClient_Plugin::getInstance();

    if (is_admin()) {
        require_once(dirname(__FILE__) . '/admin/loghero-admin.php');
    }
}
