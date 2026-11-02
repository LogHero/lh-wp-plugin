<?php
namespace LogHero\Wordpress\Test;
use \LogHero\Wordpress\LogHeroPluginSettings;
use \LogHero\Client\LogTransportType;
use \LogHero\Client\RedisOptions;


class LogHeroPluginSettingsTest extends \WP_UnitTestCase {
    public function set_up() {
        parent::set_up();
        update_option(LogHeroPluginSettings::$useSyncTransportOptionName, null);
        update_option(LogHeroPluginSettings::$disableTransportOptionName, null);
        update_option(LogHeroPluginSettings::$apiKeyOptionName, 'SOME_API_KEY');
        update_option(LogHeroPluginSettings::$redisUrlOptionName, null);
        update_option(LogHeroPluginSettings::$redisKeyPrefixOptionName, null);
        update_option(LogHeroPluginSettings::$apiEndpointOptionName, null);
    }

    public function testGetTransportTypeFromOptions() {
        static::assertEquals(LogTransportType::ASYNC, $this->createSettings()->getTransportType());
        update_option(LogHeroPluginSettings::$useSyncTransportOptionName, true);
        static::assertEquals(LogTransportType::SYNC, $this->createSettings()->getTransportType());
        update_option(LogHeroPluginSettings::$useSyncTransportOptionName, false);
        static::assertEquals(LogTransportType::ASYNC, $this->createSettings()->getTransportType());
        update_option(LogHeroPluginSettings::$disableTransportOptionName, true);
        static::assertEquals(LogTransportType::DISABLED, $this->createSettings()->getTransportType());
        update_option(LogHeroPluginSettings::$useSyncTransportOptionName, true);
        static::assertEquals(LogTransportType::DISABLED, $this->createSettings()->getTransportType());
        update_option(LogHeroPluginSettings::$disableTransportOptionName, false);
        static::assertEquals(LogTransportType::SYNC, $this->createSettings()->getTransportType());
    }

    public function testGetRedisOptions() {
        static::assertNull($this->createSettings()->getRedisOptions());
        update_option(LogHeroPluginSettings::$redisUrlOptionName, 'REDIS_URL');
        static::assertEquals('REDIS_URL', $this->createSettings()->getRedisOptions()->getRedisUrl());
        static::assertEquals(
            'io.loghero:wp:SOME_API_KEY',
            $this->createSettings()->getRedisOptions()->getRedisKeyPrefix()
        );
        update_option(LogHeroPluginSettings::$redisKeyPrefixOptionName, 'REDIS_PREFIX');
        static::assertEquals('REDIS_PREFIX', $this->createSettings()->getRedisOptions()->getRedisKeyPrefix());
        update_option(LogHeroPluginSettings::$redisKeyPrefixOptionName, '');
        static::assertEquals(
            'io.loghero:wp:SOME_API_KEY',
            $this->createSettings()->getRedisOptions()->getRedisKeyPrefix()
        );
        update_option(LogHeroPluginSettings::$redisUrlOptionName, '');
        static::assertNull($this->createSettings()->getRedisOptions());
    }

    public function testGetApiEndpointOptions() {
        static::assertNull($this->createSettings()->getApiEndpoint());
        update_option(LogHeroPluginSettings::$apiEndpointOptionName, '');
        static::assertNull($this->createSettings()->getApiEndpoint());
        update_option(LogHeroPluginSettings::$apiEndpointOptionName, 'https://my.api.endpoint/logs/');
        static::assertEquals('https://my.api.endpoint/logs/', $this->createSettings()->getApiEndpoint());
    }

    public function testSettingsAreNeverWrittenToAFile() {
        static::assertFalse(method_exists(LogHeroPluginSettings::class, 'flushToSettingsStorage'));
        static::assertFileDoesNotExist(dirname(__DIR__) . '/logs/settings.loghero.io.json');
    }

    public function testSpecifyIfAccessToStorageFolderIsRequired() {
        static::assertTrue(LogHeroPluginSettings::accessToStorageFolderIsRequired());
        update_option(LogHeroPluginSettings::$useSyncTransportOptionName, true);
        static::assertTrue(LogHeroPluginSettings::accessToStorageFolderIsRequired());
        update_option(LogHeroPluginSettings::$redisUrlOptionName, 'REDIS_URL');
        static::assertFalse(LogHeroPluginSettings::accessToStorageFolderIsRequired());
        update_option(LogHeroPluginSettings::$redisUrlOptionName, '');
        static::assertTrue(LogHeroPluginSettings::accessToStorageFolderIsRequired());
    }

    private function createSettings() {
        return new LogHeroPluginSettings();
    }
}