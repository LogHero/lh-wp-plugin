<?php

namespace LogHero\Wordpress;
use \LogHero\Client\APIKeyFileStorage;
use \LogHero\Client\APIAccess;
use LogHero\Client\APISettingsInterface;
use \LogHero\Client\LogEventFactory;
use \LogHero\Client\FileLogBuffer;
use \LogHero\Client\AsyncLogTransport;


class LogHeroPluginClient {
    private $apiKeyStorage;
    private $logEventFactory;

    public function __construct(APISettingsInterface $apiSettings, $flushEndpoint = null, $apiAccess = null) {
        $clientId = LogHeroGlobals::Instance()->getClientId();
        $this->apiKeyStorage = new APIKeyFileStorage(LogHeroGlobals::Instance()->getAPIKeyStorageFilename());
        if (!$apiAccess) {
            $apiAccess = new APIAccess($this->apiKeyStorage, $clientId, $apiSettings);
        }
        $this->logEventFactory = new LogEventFactory();
        $this->logTransport = new AsyncLogTransport(
            new FileLogBuffer(LogHeroGlobals::Instance()->getLogEventsBufferFilename()),
            $apiAccess,
            $clientId,
            $this->apiKeyStorage->getKey(),
            $flushEndpoint
        );
    }

    public function submitLogEvent() {
        $logEvent = $this->logEventFactory->create();
        if ($logEvent->getUserAgent() === LogHeroGlobals::Instance()->getClientId()) {
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