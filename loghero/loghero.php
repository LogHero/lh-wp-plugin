<?php
/*
Plugin Name: LogHero Client
Version:     0.2.0
Description: Analyze how search engines and other bots crawl and understand your web page. The official PHP Wordpress plugin for log-hero.com.
Author:      Kay Wolter
Author URI:  https://log-hero.com/
License:     MIT

Copyright (c) 2018 Cross Platform Solutions GmbH

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

*/

namespace LogHero\Wordpress;

if ( !class_exists( 'LogHeroClient_Plugin' ) ) {
    require_once __DIR__ . '/autoload.php';

    class LogHeroClient_Plugin {
        public $clientId = 'Wordpress Plugin loghero/wp@0.2.0';
        protected static $Instance = false;
        protected $apiKey;
        protected $logTransport;
        protected $logEventFactory;

        public function __construct() {
            $this->apiKey = get_option('api_key');
            $this->logEventFactory = new \LogHero\Client\LogEventFactory();
            $logBuffer = new \LogHero\Client\FileLogBuffer(__DIR__ . '/logs/buffer.loghero.io.txt');
            $apiAccess = new \LogHero\Client\APIAccess($this->apiKey, $this->clientId);
            $this->logTransport = new \LogHero\Client\AsyncLogTransport(
                $logBuffer,
                $apiAccess,
                $this->clientId,
                $this->apiKey,
                $this->flushEndpoint()
            );
            add_action('shutdown', array($this, 'submitLogEvent'));
        }

        public static function getInstance() {
            if (!self::$Instance) {
                self::$Instance = new self();
            }
            return self::$Instance;
        }

        public function submitLogEvent() {
            $logEvent = $this->logEventFactory->create();
            if ($logEvent->getUserAgent() === $this->clientId) {
                return;
            }
            $this->logTransport->submit($logEvent);
        }

        public function flush($token) {
            if ($token !== $this->apiKey) {
                throw new InvalidTokenException('Token is invalid');
            }
            $this->logTransport->dumpLogEvents();
        }

        protected function flushEndpoint() {
            # TODO Backslashes on Windows?
            $absolutePluginDirectory = plugin_dir_path( __FILE__ );
            $relativePluginDirectory = str_replace(ABSPATH, '/', $absolutePluginDirectory);
            return get_home_url() . $relativePluginDirectory . 'flush.php';
        }

    }

    LogHeroClient_Plugin::getInstance();

    if (is_admin()) {
        require_once(__DIR__ . '/admin/loghero-admin.php');
    }
}
