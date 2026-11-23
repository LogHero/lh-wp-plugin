<?php

namespace LogHero\Wordpress;
use LogHero\Client\APIAccess;
use LogHero\Client\LogEventFactory;
use LogHero\Client\FileLogBuffer;
use LogHero\Client\PermissionDeniedException;
use LogHero\Client\RedisLogBuffer;
use LogHero\Client\LogTransport;
use LogHero\Client\DisabledLogTransport;
use LogHero\Client\LogTransportType;
use LogHero\Wordpress\LogHeroPluginSettings;
use Predis\Client;

if (!defined('ABSPATH')) {
    exit;
}


class LogHeroPluginClient {
    private $logEventFactory;
    private $settings;
    private $apiSettings;
    protected $logTransport;

    public function __construct($apiAccess = null) {
        $clientId = LogHeroGlobals::Instance()->getClientId();
        $this->settings = new LogHeroPluginSettings();
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
            $this->logTransport = new WordPressDeferredLogTransport(
                $this->createLogBuffer(),
                $apiAccess
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
        catch(\Exception $e) {
            try {
                LogHeroGlobals::Instance()->errors()->writeError('unexpected', $e->getMessage());
            }
            catch(PermissionDeniedException $permissionDeniedError) {
                throw $e;
            }
        }
    }

    /**
     * Sends everything in the buffer right away. Used by the WP-Cron events.
     * Does nothing when the log transport is disabled.
     */
    public function flushBuffer() {
        if ($this->settings->getTransportType() === LogTransportType::DISABLED) {
            return;
        }
        if ($this->logTransport instanceof WordPressDeferredLogTransport) {
            $this->logTransport->flushNow();
            return;
        }
        $this->logTransport->flush();
    }

    private function createLogBuffer() {
        $redisOptions = $this->settings->getRedisOptions();
        if ($redisOptions) {
            return new RedisLogBuffer(new \Predis\Client($redisOptions->getRedisUrl()), $redisOptions);
        }
        return new FileLogBuffer(LogHeroGlobals::Instance()->getLogEventsBufferFilename());
    }

}
