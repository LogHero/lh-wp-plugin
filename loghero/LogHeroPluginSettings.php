<?php

namespace LogHero\Wordpress;
use \LogHero\Client\LogTransportType;
use LogHero\Client\RedisOptions;
use LogHero\Client\ClientIpResolver;

if (!defined('ABSPATH')) {
    exit;
}



/**
 * Plugin settings, read from wp_options only.
 *
 * Up to 0.3.0 the settings, API key included, were also mirrored into
 * logs/settings.loghero.io.json for the asynchronous flush. That file was
 * publicly readable on servers that ignore .htaccess and has been removed.
 */
class LogHeroPluginSettings {
    public static $apiKeyOptionName = 'loghero_api_key';
    public static $redisUrlOptionName = 'loghero_redis_url';
    public static $redisKeyPrefixOptionName = 'loghero_redis_key_prefix';
    public static $apiEndpointOptionName = 'loghero_api_endpoint';
    public static $useSyncTransportOptionName = 'loghero_use_sync_transport';
    public static $disableTransportOptionName = 'loghero_disable_transport';
    public static $proxyHeaderOptionName = 'loghero_proxy_header';
    public static $trustedProxiesOptionName = 'loghero_trusted_proxies';
    public static $defaultRedisKeyPrefix = 'io.loghero:wp';

    private $apiKey;
    private $transportType;
    private $redisOptions;
    private $apiEndpoint;
    private $proxyHeader;
    private $trustedProxies;

    public function __construct() {
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

    public function getProxyHeader() {
        return $this->proxyHeader;
    }

    public function getTrustedProxies() {
        return $this->trustedProxies;
    }

    public static function parseTrustedProxies($value) {
        if (!is_string($value) || trim($value) === '') {
            return array();
        }
        $proxies = array();
        foreach (preg_split('/[\s,]+/', $value, -1, PREG_SPLIT_NO_EMPTY) as $proxy) {
            if (ClientIpResolver::isValidRange($proxy)) {
                $proxies[] = $proxy;
            }
        }
        return array_values(array_unique($proxies));
    }

    public static function accessToStorageFolderIsRequired() {
        $settings = new self();
        return $settings->getRedisOptions() === null;
    }

    public static function buildDefaultRedisKeyPrefix($apiKey) {
        return static::$defaultRedisKeyPrefix . ':' . $apiKey;
    }

    private function initializeSettings() {
        $this->apiKey = get_option(static::$apiKeyOptionName);

        # LOG-182
        if (!$this->apiKey) {
            $this->apiKey = get_option('api_key');
        }

        $apiEndpoint = get_option(static::$apiEndpointOptionName);
        if ($apiEndpoint) {
            $this->apiEndpoint = $apiEndpoint;
        }
        $redisUrl = get_option(static::$redisUrlOptionName);
        if($redisUrl) {
            $redisKeyPrefix = get_option(static::$redisKeyPrefixOptionName);
            if (!$redisKeyPrefix) {
                $redisKeyPrefix = static::buildDefaultRedisKeyPrefix($this->apiKey);
            }
            $this->redisOptions = new RedisOptions($redisUrl, $redisKeyPrefix);
        }
        $proxyHeader = get_option(static::$proxyHeaderOptionName);
        $this->proxyHeader = $proxyHeader ? $proxyHeader : null;
        $this->trustedProxies = static::parseTrustedProxies(get_option(static::$trustedProxiesOptionName));
        $this->initializeTransportType();
    }

    private function initializeTransportType() {
        if (get_option(static::$disableTransportOptionName)) {
            $this->transportType = LogTransportType::DISABLED;
            return;
        }
        if (get_option(static::$useSyncTransportOptionName)) {
            $this->transportType = LogTransportType::SYNC;
            return;
        }
        $this->transportType = LogTransportType::ASYNC;
    }

}
