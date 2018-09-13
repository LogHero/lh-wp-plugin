<?php

namespace LogHero\Wordpress;
use \LogHero\Client\LogTransportType;
use LogHero\Client\RedisOptions;


class LogHeroPluginSettings {
    public static $apiKeyOptionName = 'loghero_api_key';
    public static $redisUrlOptionName = 'loghero_redis_url';
    public static $redisKeyPrefixOptionName = 'loghero_redis_key_prefix';
    public static $apiEndpointOptionName = 'loghero_api_endpoint';
    public static $useSyncTransportOptionName = 'loghero_use_sync_transport';
    public static $disableTransportOptionName = 'loghero_disable_transport';
    public static $defaultRedisKeyPrefix = 'io.loghero:wp';

    private $settingsStorage;
    private $hasWordPress;

    private $apiKey;
    private $transportType;
    private $redisOptions;
    private $apiEndpoint;

    public function __construct($settingsStorage = null, $hasWordPress = null) {
        if ($hasWordPress === null) {
            $hasWordPress = function_exists('get_option') ? True : False;
        }
        $this->hasWordPress = $hasWordPress;
        $this->settingsStorage = $settingsStorage;
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

    public function getApiEndpoint() {
        return $this->apiEndpoint;
    }

    public function flushToSettingsStorage() {
        $optionsToStore = static::getOptionsToStore();
        $jsonData = array();
        foreach($optionsToStore as $option) {
            $jsonData[$option] = get_option($option);
        }
        if ($this->settingsStorage) {
            $this->settingsStorage->set(json_encode($jsonData));
        }
    }

    public static function accessToLogsFolderIsRequired() {
        $settings = new self();
        if ($settings->isAsyncFlushInternally()) {
            return true;
        }
        return $settings->getRedisOptions() === null;
    }

    public static function isAsyncFlush() {
        $settings = new self();
        return $settings->isAsyncFlushInternally();
    }

    public static function buildDefaultRedisKeyPrefix($apiKey) {
        return static::$defaultRedisKeyPrefix . ':' . $apiKey;
    }

    private function initializeSettings() {
        $jsonData = null;
        $storageData = $this->settingsStorage ? $this->settingsStorage->get() : null;
        if ($storageData) {
            $jsonData = json_decode($storageData, true);
        }
        $this->apiKey = $this->getOption(static::$apiKeyOptionName, $jsonData);

        # LOG-182
        if (!$this->apiKey) {
            $this->apiKey = $this->getOption('api_key', $jsonData);
        }

        $apiEndpoint = $this->getOption(static::$apiEndpointOptionName, $jsonData);
        if ($apiEndpoint) {
            $this->apiEndpoint = $apiEndpoint;
        }
        $redisUrl = $this->getOption(static::$redisUrlOptionName, $jsonData);
        if($redisUrl) {
            $redisKeyPrefix = $this->getOption(static::$redisKeyPrefixOptionName, $jsonData);
            if (!$redisKeyPrefix) {
                $redisKeyPrefix = static::buildDefaultRedisKeyPrefix($this->apiKey);
            }
            $this->redisOptions = new RedisOptions($redisUrl, $redisKeyPrefix);
        }
        $this->initializeTransportType($jsonData);
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

    private function isAsyncFlushInternally() {
        return $this->getTransportType() === LogTransportType::ASYNC;
    }

    private function initializeTransportType($jsonData) {
        $disableTransport = $this->getOption(static::$disableTransportOptionName, $jsonData);
        if ($disableTransport) {
            $this->transportType = LogTransportType::DISABLED;
            return;
        }
        $useSyncTransport = $this->getOption(static::$useSyncTransportOptionName, $jsonData);
        if ($useSyncTransport) {
            $this->transportType = LogTransportType::SYNC;
            return;
        }
        $this->transportType = LogTransportType::ASYNC;
    }

    private static function getOptionsToStore() {
        return array(
            static::$useSyncTransportOptionName,
            static::$disableTransportOptionName,
            static::$apiKeyOptionName,
            static::$redisUrlOptionName,
            static::$redisKeyPrefixOptionName,
            static::$apiEndpointOptionName
        );
    }

}