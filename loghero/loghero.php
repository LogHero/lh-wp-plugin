<?php
/*
Plugin Name: LogHero Client
Version:     0.1.2
Description: The official PHP Wordpress plugin for loghero.io.
Author:      Kay Wolter
Author URI:  https://www.funktionswerk.de/
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

use LogHero\Client\FileLogBuffer;

if ( !class_exists( 'LogHeroClient_Plugin' ) ) {
    require_once __DIR__ . '/sdk/src/event/LogEventFactory.php';
    require_once __DIR__ . '/sdk/src/buffer/FileLogBuffer.php';
    require_once __DIR__ . '/sdk/src/http/APIAccess.php';
    require_once __DIR__ . '/sdk/src/transport/LogTransport.php';

    class LogHeroClient_Plugin {
        public $clientId = 'Wordpress Plugin loghero/wp@0.1.2';
        protected static $Instance = false;
        protected $apiKey;
        protected $logTransport;
        protected $logEventFactory;

        public function __construct() {
            $this->apiKey = get_option('api_key');
            $this->logEventFactory = new \LogHero\Client\LogEventFactory();
            $logBuffer = new \LogHero\Client\FileLogBuffer(__DIR__ . '/logs/buffer.loghero.io.txt');
            $apiAccess = new \LogHero\Client\APIAccess($this->apiKey, $this->clientId);
            $this->logTransport = new \LogHero\Client\LogTransport($logBuffer, $apiAccess);
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
            // TODO: Test this:
            if ($logEvent->getUserAgent() == $this->clientId) {
                return;
            }
            $this->logTransport->submit($logEvent);
        }

        # TODO Test this function
        private function triggerFlush() {
            # TODO Backslashes on Windows?
            $absolutePluginDirectory = plugin_dir_path( __FILE__ );
            $relativePluginDirectory = str_replace(ABSPATH, '/', $absolutePluginDirectory);
            $triggerEndpoint = get_home_url() . $relativePluginDirectory . 'flush.php';
            $curlClient = new \LogHero\Client\CurlClient($triggerEndpoint);
            $curlClient->setOpt(CURLOPT_HTTPHEADER, array(
                'Authorization: '.$this->apiKey,
                'User-Agent: '.$this->clientId
            ));
            $curlClient->setOpt(CURLOPT_CUSTOMREQUEST, 'GET');
            $curlClient->exec();
            $status = $curlClient->getInfo(CURLINFO_HTTP_CODE);
            if ( $status >= 300 ) {
                $errorMessage = $curlClient->error();
                $curlClient->close();
                throw new \LogHero\Client\APIAccessException(
                    'Call to URL '.$triggerEndpoint.' failed with status '.$status.'; Message: '.$errorMessage
                );
            }
            $curlClient->close();
        }

        public function flush() {
            $this->apiClient->flush();
        }

    }

    LogHeroClient_Plugin::getInstance();

    if (is_admin()) {
        require_once(__DIR__ . '/admin/loghero-admin.php');
    }
}
