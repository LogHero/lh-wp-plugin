<?php
namespace LogHero\Wordpress\Test;
use \LogHero\Wordpress\LogHeroPluginSettings;
use \LogHero\Client\LogTransportType;
use \LogHero\Client\RedisOptions;


class LogHeroPluginSettingsTest extends \WP_UnitTestCase {
    private $settingsFilename = __DIR__ . '/logs/settings.loghero.io.json';

    public function setUp() {
        update_option(LogHeroPluginSettings::$useSyncTransportOptionName, null);
        update_option(LogHeroPluginSettings::$apiKeyOptionName, 'SOME_API_KEY');
        update_option(LogHeroPluginSettings::$redisUrlOptionName, null);
        update_option(LogHeroPluginSettings::$redisKeyPrefixOptionName, null);
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
    }

    public function testGetRedisOptions() {
        static::assertNull($this->createSettings()->getRedisOptions());
        update_option(LogHeroPluginSettings::$redisUrlOptionName, 'REDIS_URL');
        static::assertEquals('REDIS_URL', $this->createSettings()->getRedisOptions()->getRedisUrl());
        static::assertEquals(
            RedisOptions::$defaultRedisKeyPredix,
            $this->createSettings()->getRedisOptions()->getRedisKeyPrefix()
        );
        update_option(LogHeroPluginSettings::$redisKeyPrefixOptionName, 'REDIS_PREFIX');
        static::assertEquals('REDIS_PREFIX', $this->createSettings()->getRedisOptions()->getRedisKeyPrefix());
        update_option(LogHeroPluginSettings::$redisKeyPrefixOptionName, '');
        static::assertEquals(
            RedisOptions::$defaultRedisKeyPredix,
            $this->createSettings()->getRedisOptions()->getRedisKeyPrefix()
        );
        update_option(LogHeroPluginSettings::$redisUrlOptionName, '');
        static::assertNull($this->createSettings()->getRedisOptions());
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

    private function createSettings($hasWordPress = null) {
        return new LogHeroPluginSettings($hasWordPress, $this->settingsFilename);
    }
}