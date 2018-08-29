<?php

namespace LogHero\Wordpress;
use LogHero\Client\APIKeyFileStorage;
use LogHero\Client\APIAccess;
use LogHero\Client\APISettingsInterface;
use LogHero\Client\LogEventFactory;
use LogHero\Client\FileLogBuffer;
use LogHero\Client\RedisLogBuffer;
use LogHero\Client\LogTransport;
use LogHero\Client\AsyncLogTransport;
use LogHero\Client\AsyncFlushFailedException;
use LogHero\Client\LogTransportType;
use LogHero\Wordpress\LogHeroPluginSettings;
use LogHero\Client\FileStorage;
use Predis\Client;


class LogHeroPluginClient {
    private $apiKeyStorage;
    private $logEventFactory;
    private $settings;
    protected $logTransport;

    public function __construct($flushEndpoint = null, $apiAccess = null) {
        $clientId = LogHeroGlobals::Instance()->getClientId();
        $this->apiKeyStorage = new APIKeyFileStorage(LogHeroGlobals::Instance()->getAPIKeyStorageFilename());
        $this->settings = new LogHeroPluginSettings(static::createSettingsStorage());
        if (!$apiAccess) {
            $apiAccess = new APIAccess($this->apiKeyStorage, $clientId, new LogHeroAPISettings($this->settings));
        }
        $this->logEventFactory = new LogEventFactory();
        $logTransportType = $this->settings->getTransportType();
        if ($logTransportType == LogTransportType::SYNC) {
            $this->logTransport = new LogTransport(
                $this->createLogBuffer(),
                $apiAccess
            );
        }
        else {
            $this->logTransport = new AsyncLogTransport(
                $this->createLogBuffer(),
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

    # TODO: No storage in sync mode (read from DB only as workaround for permission denied)
    public static function createSettingsStorage() {
        return new FileStorage(__DIR__ . '/logs/settings.loghero.io.json');
    }

    private function createLogBuffer() {
        $redisOptions = $this->settings->getRedisOptions();
        if ($redisOptions) {
            return new RedisLogBuffer(new \Predis\Client($redisOptions->getRedisUrl()), $redisOptions);
        }
        return new FileLogBuffer(LogHeroGlobals::Instance()->getLogEventsBufferFilename());
    }

}