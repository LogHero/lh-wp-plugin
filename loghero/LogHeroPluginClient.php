<?php

namespace LogHero\Wordpress;
use \LogHero\Client\APIKeyFileStorage;
use \LogHero\Client\APIAccess;
use \LogHero\Client\LogEventFactory;
use \LogHero\Client\FileLogBuffer;
use \LogHero\Client\AsyncLogTransport;


class LogHeroPluginClient {
    private $apiKeyStorage;
    private $logEventFactory;
    private $settings;

    public function __construct($flushEndpoint = null, $apiKeyStorage = null, $logBuffer = null, $apiAccess = null) {
        $this->settings = new LogHeroSettings();
        if (!$apiKeyStorage) {
            $apiKeyStorage = new APIKeyFileStorage($this->settings->apiKeyStorageFile);
        }
        $this->apiKeyStorage = $apiKeyStorage;
        if (!$apiAccess) {
            $apiAccess = new APIAccess($this->apiKeyStorage, $this->settings->clientId);
        }
        $this->logEventFactory = new LogEventFactory();
        if (!$logBuffer) {
            $logBuffer = new FileLogBuffer($this->settings->logEventsBufferFile);
        }
        $this->logTransport = new AsyncLogTransport(
            $logBuffer,
            $apiAccess,
            $this->settings->clientId,
            $this->apiKeyStorage->getKey(),
            $flushEndpoint
        );
    }

    public function submitLogEvent() {
        $logEvent = $this->logEventFactory->create();
        if ($logEvent->getUserAgent() === $this->settings->clientId) {
            return;
        }
        $this->logTransport->submit($logEvent);
    }

    public function flush($token) {
        if ($token !== $this->apiKeyStorage->getKey()) {
            throw new InvalidTokenException('Token is invalid');
        }
        $this->logTransport->dumpLogEvents();
    }

    public static function refreshAPIKey($apiKey) {
        $settings = new LogHeroSettings();
        $apiKeyStorage = new APIKeyFileStorage($settings->apiKeyStorageFile);
        $apiKeyStorage->setKey($apiKey);
    }
}