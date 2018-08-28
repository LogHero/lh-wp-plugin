<?php

namespace LogHero\Wordpress;
use \LogHero\Client\LogTransportType;


class LogHeroPluginSettings {
    public static $useSyncTransportOptionName = 'use_sync_transport';

    private $settingsFilename;
    private $hasWordPress;

    private $transportType;

    public function __construct($hasWordPress = null, $settingsFilename = null) {
        if (!$settingsFilename) {
            $settingsFilename = __DIR__ . '/logs/settings.loghero.io.json';
        }
        if ($hasWordPress === null) {
            $hasWordPress = function_exists('get_option') ? True : False;
        }
        $this->hasWordPress = $hasWordPress;
        $this->settingsFilename = $settingsFilename;
        $this->initializeSettings();
    }

    public function getTransportType() {
        return $this->transportType;

    }

    public function flushToSettingsStorage() {
        $jsonData = array(
            static::$useSyncTransportOptionName => get_option(static::$useSyncTransportOptionName)
        );
        file_put_contents($this->settingsFilename, json_encode($jsonData));
        chmod($this->settingsFilename, 0666);
    }

    private function initializeSettings() {
        $jsonData = null;
        if (file_exists($this->settingsFilename)) {
            $jsonString = file_get_contents($this->settingsFilename);
            $jsonData = json_decode($jsonString, true);
        }
        $useSyncTransport = $this->getOption(static::$useSyncTransportOptionName, $jsonData);
        if ($useSyncTransport) {
            $this->transportType = LogTransportType::SYNC;
            return;
        }
        $this->transportType = LogTransportType::ASYNC;
    }

    private function getOption($key, $jsonData) {
        if ($this->hasWordPress) {
            return get_option($key);
        }
        if ($jsonData) {
            return $jsonData[$key];
        }
        return null;
    }

}