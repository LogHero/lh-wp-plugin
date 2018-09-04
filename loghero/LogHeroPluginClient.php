<?php

namespace LogHero\Wordpress;
use LogHero\Client\APIAccess;
use LogHero\Client\LogEventFactory;
use LogHero\Client\FileLogBuffer;
use LogHero\Client\PermissionDeniedException;
use LogHero\Client\RedisLogBuffer;
use LogHero\Client\LogTransport;
use LogHero\Client\DisabledLogTransport;
use LogHero\Client\AsyncLogTransport;
use LogHero\Client\AsyncFlushFailedException;
use LogHero\Client\LogTransportType;
use LogHero\Wordpress\LogHeroPluginSettings;
use LogHero\Client\FileStorage;
use Predis\Client;


class LogHeroPluginClient {
    private $logEventFactory;
    private $settings;
    private $apiSettings;
    protected $logTransport;

    public function __construct($flushEndpoint = null, $apiAccess = null) {
        $clientId = LogHeroGlobals::Instance()->getClientId();
        $this->settings = new LogHeroPluginSettings(static::createSettingsStorage());
        $this->apiSettings = new LogHeroAPISettings($this->settings);
        if (!$apiAccess) {
            $apiAccess = new APIAccess($clientId, $this->apiSettings);
        }
        $this->logEventFactory = new LogEventFactory();
        $logTransportType = $this->settings->getTransportType();
        if ($logTransportType === LogTransportType::DISABLED) {
            $this->logTransport = new DisabledLogTransport(
                $this->createLogBuffer()
            );
        }
        else if ($logTransportType === LogTransportType::SYNC) {
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
                $this->apiSettings->getKey(),
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
            try {
                LogHeroGlobals::Instance()->errors()->writeError('unexpected', $e);
            }
            catch(PermissionDeniedException $permissionDeniedError) {
                throw $e;
            }
        }
    }

    public function flush($token) {
        if ($token !== $this->apiSettings->getKey()) {
            throw new InvalidTokenException('Token is invalid');
        }
        if ($this->settings->getTransportType() !== LogTransportType::ASYNC) {
            throw new InvalidTransportTypeException('Flush action needs async transport type');
        }
        $this->logTransport->dumpLogEvents();
    }

    public static function createSettingsStorage() {
        if (LogHeroPluginSettings::isAsyncFlush()) {
            return new FileStorage(LogHeroGlobals::Instance()->getSettingsStorageFilename());
        }
        return null;
    }

    private function createLogBuffer() {
        $redisOptions = $this->settings->getRedisOptions();
        if ($redisOptions) {
            return new RedisLogBuffer(new \Predis\Client($redisOptions->getRedisUrl()), $redisOptions);
        }
        return new FileLogBuffer(LogHeroGlobals::Instance()->getLogEventsBufferFilename());
    }

}