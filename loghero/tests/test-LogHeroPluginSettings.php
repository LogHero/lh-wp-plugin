<?php
namespace LogHero\Wordpress\Test;
use \LogHero\Wordpress\LogHeroPluginSettings;
use \LogHero\Client\LogTransportType;


class LogHeroPluginSettingsTest extends \WP_UnitTestCase {

    public function setUp() {
        update_option('use_sync_transport', null);
    }

    public function testGetTransportTypeFromOptions() {
        static::assertEquals(LogTransportType::ASYNC, LogHeroPluginSettings::getTransportType());
        update_option('use_sync_transport', true);
        static::assertEquals(LogTransportType::SYNC, LogHeroPluginSettings::getTransportType());
        update_option('use_sync_transport', false);
        static::assertEquals(LogTransportType::ASYNC, LogHeroPluginSettings::getTransportType());
    }
}