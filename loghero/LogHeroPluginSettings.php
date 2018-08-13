<?php

namespace LogHero\Wordpress;
use \LogHero\Client\LogTransportType;


class LogHeroPluginSettings {

    public static function getTransportType() {
        $useSyncTransport = static::getOption('use_sync_transport');
        if ($useSyncTransport) {
            return LogTransportType::SYNC;
        }
        return LogTransportType::ASYNC;
    }

    public static function getOption($key) {
        return function_exists('get_option') ? get_option($key) : null;
    }
}