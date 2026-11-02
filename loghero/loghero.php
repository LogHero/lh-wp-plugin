<?php
/*
Plugin Name:       LogHero Client
Plugin URI:        https://wordpress.org/plugins/loghero/
Description:       Analyze how search engines and other bots crawl and understand your web page. The official WordPress plugin for log-hero.com.
Version:           0.4.0
Requires at least: 6.0
Requires PHP:      7.4
Author:            Kay Wolter
Author URI:        https://log-hero.com/
Text Domain:       loghero
License:           MIT
License URI:       https://opensource.org/licenses/MIT
*/

/*
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
use LogHero\Client\PermissionDeniedException;

if (!defined('ABSPATH')) {
    exit;
}


if (!class_exists('\LogHero\Wordpress\LogHero_Plugin', false)) {
    require_once __DIR__ . '/autoload.php';

    class LogHero_Plugin {
        protected static $Instance = false;
        protected $logHeroClient;

        public function __construct() {
            add_action('shutdown', array($this, 'onShutdown'));
        }

        public static function getInstance() {
            if (!self::$Instance) {
                self::$Instance = new self();
            }
            return self::$Instance;
        }

        /**
         * The client is created on shutdown rather than while plugins load, so
         * WordPress is fully available (pluggable functions, upload directory).
         */
        public function onShutdown() {
            # Only web requests are logged. WP-CLI runs have no request to log, and
            # during installation the database is not ready.
            if (PHP_SAPI === 'cli' || wp_installing()) {
                return;
            }
            try {
                if (!$this->logHeroClient) {
                    $this->logHeroClient = new LogHeroPluginClient();
                }
                $this->logHeroClient->submitLogEvent();
            }
            catch (PermissionDeniedException $e) {
            }
            catch (\Exception $e) {
            }
        }
    }

    register_activation_hook(__FILE__, array('\LogHero\Wordpress\LogHeroUpgrade', 'activate'));
    register_deactivation_hook(__FILE__, array('\LogHero\Wordpress\LogHeroUpgrade', 'deactivate'));

    LogHeroCron::setup();
    LogHeroUpgrade::setup();
    LogHero_Plugin::getInstance();

    if (is_admin()) {
        require_once(__DIR__ . '/admin/loghero-admin.php');
    }
}
