<?php

namespace LogHero\Wordpress;
use \LogHero\Client\LogTransportType;


class LogHeroPluginSettings {

    public static function getTransportType() {
        $useSyncTransport = get_option('use_sync_transport');
        if ($useSyncTransport) {
            return LogTransportType::Sync;
        }
        return LogTransportType::Async;
    }

}