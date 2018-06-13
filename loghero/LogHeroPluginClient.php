<?php

namespace LogHero\Wordpress;


class LogHeroPluginClient {
    private $apiKeyStorage;
    private $logEventFactory;

    public function __construct($flushEndpoint = null, $apiKeyStorage = null, $logBuffer = null, $apiAccess = null) {
        if (!$apiKeyStorage) {
            $apiKeyStorage = new \LogHero\Client\APIKeyFileStorage(LogHeroSettings::$apiKeyStorageFile);
        }
        $this->apiKeyStorage = $apiKeyStorage;
        if (!$apiAccess) {
            $apiAccess = new \LogHero\Client\APIAccess($this->apiKeyStorage, LogHeroSettings::$clientId);
        }
        $this->logEventFactory = new \LogHero\Client\LogEventFactory();
        if (!$logBuffer) {
            $logBuffer = new \LogHero\Client\FileLogBuffer(LogHeroSettings::$logEventsBufferFile);
        }
        $this->logTransport = new \LogHero\Client\AsyncLogTransport(
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
}