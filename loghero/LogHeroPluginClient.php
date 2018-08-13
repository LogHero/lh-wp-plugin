<?php

namespace LogHero\Wordpress;
use LogHero\Client\APIKeyFileStorage;
use LogHero\Client\APIAccess;
use LogHero\Client\APISettingsInterface;
use LogHero\Client\LogEventFactory;
use LogHero\Client\FileLogBuffer;
use LogHero\Client\LogTransport;
use LogHero\Client\AsyncLogTransport;
use LogHero\Client\AsyncFlushFailedException;
use LogHero\Client\LogTransportType;
use LogHero\Wordpress\LogHeroPluginSettings;


class LogHeroPluginClient {
    private $apiKeyStorage;
    private $logEventFactory;
    protected $logTransport;

    public function __construct(APISettingsInterface $apiSettings, $flushEndpoint = null, $apiAccess = null) {
        $clientId = LogHeroGlobals::Instance()->getClientId();
        $this->apiKeyStorage = new APIKeyFileStorage(LogHeroGlobals::Instance()->getAPIKeyStorageFilename());
        if (!$apiAccess) {
            $apiAccess = new APIAccess($this->apiKeyStorage, $clientId, $apiSettings);
        }
        $this->logEventFactory = new LogEventFactory();
        $logTransportType = LogHeroPluginSettings::getTransportType();
        if ($logTransportType == LogTransportType::SYNC) {
            $this->logTransport = new LogTransport(
                new FileLogBuffer(LogHeroGlobals::Instance()->getLogEventsBufferFilename()),
                $apiAccess
            );
        }
        else {
            $this->logTransport = new AsyncLogTransport(
                new FileLogBuffer(LogHeroGlobals::Instance()->getLogEventsBufferFilename()),
                $apiAccess,
                $clientId,
                $this->apiKeyStorage->getKey(),
                $flushEndpoint
            );
        }
    }

    public function submitLogEvent() {
        try {
            $logEvent = $this->logEventFactory->create();
            if ($logEvent->getUserAgent() === LogHeroGlobals::Instance()->getClientId()) {
                return;
            }
            $this->logTransport->submit($logEvent);
        }
        catch(AsyncFlushFailedException $e) {
            LogHeroGlobals::Instance()->errors()->writeError('async-flush', $e);
        }
        catch(\Exception $e) {
            LogHeroGlobals::Instance()->errors()->writeError('unexpected', $e);
        }
    }

    public function flush($token) {
        if ($token !== $this->apiKeyStorage->getKey()) {
            throw new InvalidTokenException('Token is invalid');
        }
        $this->logTransport->dumpLogEvents();
    }

}