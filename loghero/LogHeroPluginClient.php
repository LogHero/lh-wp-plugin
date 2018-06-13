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

    public function __construct($flushEndpoint = null, $apiKeyStorage = null, $logBuffer = null, $apiAccess = null) {
        if (!$apiKeyStorage) {
            $apiKeyStorage = new APIKeyFileStorage(LogHeroSettings::$apiKeyStorageFile);
        }
        $this->apiKeyStorage = $apiKeyStorage;
        if (!$apiAccess) {
            $apiAccess = new APIAccess($this->apiKeyStorage, LogHeroSettings::$clientId);
        }
        $this->logEventFactory = new LogEventFactory();
        if (!$logBuffer) {
            $logBuffer = new FileLogBuffer(LogHeroSettings::$logEventsBufferFile);
        }
        $this->logTransport = new AsyncLogTransport(
            $logBuffer,
            $apiAccess,
            LogHeroSettings::$clientId,
            $this->apiKeyStorage->getKey(),
            $flushEndpoint
        );
    }

    public function submitLogEvent() {
        $logEvent = $this->logEventFactory->create();
        if ($logEvent->getUserAgent() === LogHeroSettings::$clientId) {
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
        $apiKeyStorage = new APIKeyFileStorage(LogHeroSettings::$apiKeyStorageFile);
        $apiKeyStorage->setKey($apiKey);
    }
}