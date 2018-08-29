<?php
namespace LogHero\Wordpress\Test;
use \LogHero\Wordpress\LogHeroAPISettings;
use LogHero\Wordpress\LogHeroPluginSettings;


class LogHeroAPISettingsTest extends \WP_UnitTestCase {

    public function setUp() {
        update_option(LogHeroPluginSettings::$apiEndpointOptionName, null);
    }

    public function testProvideDefaultSettings() {
        $settings = new LogHeroAPISettings(new LogHeroPluginSettings());
        static::assertEquals('https://api.loghero.io/logs/', $settings->getAPILogPackageEndpoint());
    }

    public function testProvideCustomizedSettings() {
        update_option(LogHeroPluginSettings::$apiEndpointOptionName, 'https://test.loghero.io/logs/');
        $settings = new LogHeroAPISettings(new LogHeroPluginSettings());
        static::assertEquals('https://test.loghero.io/logs/', $settings->getAPILogPackageEndpoint());
    }
}