<?php
namespace LogHero\Wordpress\Test;
use LogHero\Client\FileStorage;
use \LogHero\Wordpress\LogHeroPluginSettings;
use \LogHero\Client\LogTransportType;
use \LogHero\Client\RedisOptions;


class LogHeroPluginSettingsTest extends \WP_UnitTestCase {
    private $settingsFilename = __DIR__ . '/logs/settings.loghero.io.json';

    public function setUp() {
        update_option(LogHeroPluginSettings::$useSyncTransportOptionName, null);
        update_option(LogHeroPluginSettings::$disableTransportOptionName, null);
        update_option(LogHeroPluginSettings::$apiKeyOptionName, 'SOME_API_KEY');
        update_option(LogHeroPluginSettings::$redisUrlOptionName, null);
        update_option(LogHeroPluginSettings::$redisKeyPrefixOptionName, null);
        update_option(LogHeroPluginSettings::$apiEndpointOptionName, null);
    }

    public function tearDown() {
        if(file_exists($this->settingsFilename)) {
            unlink($this->settingsFilename);
        }
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

    public function testFlushSettingsToStorage() {
        update_option(LogHeroPluginSettings::$useSyncTransportOptionName, true);
        update_option(LogHeroPluginSettings::$redisUrlOptionName, 'REDIS_URL');
        update_option(LogHeroPluginSettings::$redisKeyPrefixOptionName, 'REDIS_PREFIX');
        $this->createSettings()->flushToSettingsStorage();
        $json = file_get_contents($this->settingsFilename);
        $jsonData = json_decode($json, true);
        static::assertTrue($jsonData[LogHeroPluginSettings::$useSyncTransportOptionName]);
        static::assertEquals('SOME_API_KEY', $jsonData[LogHeroPluginSettings::$apiKeyOptionName]);
        static::assertEquals('REDIS_URL', $jsonData[LogHeroPluginSettings::$redisUrlOptionName]);
        static::assertEquals('REDIS_PREFIX', $jsonData[LogHeroPluginSettings::$redisKeyPrefixOptionName]);
    }

    public function testReadSettingsFromStorage() {
        file_put_contents($this->settingsFilename, '{
        "' . LogHeroPluginSettings::$useSyncTransportOptionName . '": true,
        "' . LogHeroPluginSettings::$apiKeyOptionName . '": "API_KEY_FROM_STORAGE"
        }');
        $hasWordPress = false;
        $settings = $this->createSettings($hasWordPress);
        static::assertEquals(LogTransportType::SYNC, $settings->getTransportType());
        static::assertEquals('API_KEY_FROM_STORAGE', $settings->getApiKey());
    }

    public function testIgnoreStorageIfNotProvided() {
        $settingsWithoutStorage = new LogHeroPluginSettings();
        static::assertEquals('SOME_API_KEY', $settingsWithoutStorage->getApiKey());
        $settingsWithoutStorage->flushToSettingsStorage();
        $settingsWithoutStorageAndWordPress = new LogHeroPluginSettings(null, False);
        static::assertNull($settingsWithoutStorageAndWordPress->getApiKey());
    }

    public function testSpecifyIfAsyncFlushIsActivated() {
        static::assertTrue(LogHeroPluginSettings::isAsyncFlush());
        update_option(LogHeroPluginSettings::$useSyncTransportOptionName, true);
        static::assertFalse(LogHeroPluginSettings::isAsyncFlush());
        update_option(LogHeroPluginSettings::$useSyncTransportOptionName, false);
        update_option(LogHeroPluginSettings::$disableTransportOptionName, true);
        static::assertFalse(LogHeroPluginSettings::isAsyncFlush());
    }

    public function testSpecifyIfAccessToLogsFolderIsRequired() {
        static::assertTrue(LogHeroPluginSettings::accessToLogsFolderIsRequired());
        update_option(LogHeroPluginSettings::$useSyncTransportOptionName, true);
        static::assertTrue(LogHeroPluginSettings::accessToLogsFolderIsRequired());
        update_option(LogHeroPluginSettings::$redisUrlOptionName, 'REDIS_URL');
        static::assertFalse(LogHeroPluginSettings::accessToLogsFolderIsRequired());
        update_option(LogHeroPluginSettings::$useSyncTransportOptionName, false);
        static::assertTrue(LogHeroPluginSettings::accessToLogsFolderIsRequired());
    }

    private function createSettings($hasWordPress = null) {
        return new LogHeroPluginSettings(new FileStorage($this->settingsFilename), $hasWordPress);
    }
}