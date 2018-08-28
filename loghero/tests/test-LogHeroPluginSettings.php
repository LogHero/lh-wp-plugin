<?php
namespace LogHero\Wordpress\Test;
use \LogHero\Wordpress\LogHeroPluginSettings;
use \LogHero\Client\LogTransportType;


class LogHeroPluginSettingsTest extends \WP_UnitTestCase {
    private $settingsFilename = __DIR__ . '/logs/settings.loghero.io.json';

    public function setUp() {
        update_option(LogHeroPluginSettings::$useSyncTransportOptionName, null);
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

    public function testFlushSettingsToStorage() {
        $this->createSettings()->flushToSettingsStorage();
        $json = file_get_contents($this->settingsFilename);
        $jsonData = json_decode($json, true);
        static::assertFalse($jsonData[LogHeroPluginSettings::$useSyncTransportOptionName]);
    }

    public function testReadSettingsFromStorage() {
        file_put_contents($this->settingsFilename, '{"' . LogHeroPluginSettings::$useSyncTransportOptionName . '": true}');
        $hasWordPress = false;
        $settings = $this->createSettings($hasWordPress);
        static::assertEquals(LogTransportType::SYNC, $settings->getTransportType());
    }

    private function createSettings($hasWordPress = null) {
        return new LogHeroPluginSettings($hasWordPress, $this->settingsFilename);
    }
}