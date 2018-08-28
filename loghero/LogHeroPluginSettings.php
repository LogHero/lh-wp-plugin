<?php

namespace LogHero\Wordpress;
use \LogHero\Client\LogTransportType;
use LogHero\Client\RedisOptions;


class LogHeroPluginSettings {
    public static $useSyncTransportOptionName = 'use_sync_transport';
    public static $apiKeyOptionName = 'api_key';
    public static $redisUrlOptionName = 'redis_url';
    public static $redisKeyPrefixOptionName = 'redis_key_prefix';

    private $settingsFilename;
    private $hasWordPress;

    private $apiKey;
    private $transportType;
    private $redisOptions;

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

    public function getApiKey() {
        return $this->apiKey;
    }

    public function getTransportType() {
        return $this->transportType;

    }

    public function getRedisOptions() {
        return $this->redisOptions;
    }

    public function flushToSettingsStorage() {
        $optionsToStore = static::getOptionsToStore();
        $jsonData = array();
        foreach($optionsToStore as $option) {
            $jsonData[$option] = get_option($option);
        }
        file_put_contents($this->settingsFilename, json_encode($jsonData));
        chmod($this->settingsFilename, 0666);
    }

    private function initializeSettings() {
        $jsonData = null;
        if (file_exists($this->settingsFilename)) {
            $jsonString = file_get_contents($this->settingsFilename);
            $jsonData = json_decode($jsonString, true);
        }
        $this->apiKey = $this->getOption(static::$apiKeyOptionName, $jsonData);
        $redisUrl = $this->getOption(static::$redisUrlOptionName, $jsonData);
        if($redisUrl) {
            $redisKeyPrefix = $this->getOption(static::$redisKeyPrefixOptionName, $jsonData);
            $this->redisOptions = new RedisOptions($redisUrl, $redisKeyPrefix);
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
            return array_key_exists($key, $jsonData) ? $jsonData[$key] : null;
        }
        return null;
    }

    private static function getOptionsToStore() {
        return array(
            static::$useSyncTransportOptionName,
            static::$apiKeyOptionName,
            static::$redisUrlOptionName,
            static::$redisKeyPrefixOptionName
        );
    }

}