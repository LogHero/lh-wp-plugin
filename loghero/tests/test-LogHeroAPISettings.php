<?php
namespace LogHero\Wordpress;

class LogHeroAPISettingsTest extends \WP_UnitTestCase {
    private $devSettingsFilename;

    public function setUp() {
        $this->devSettingsFilename = __DIR__ . '/logs/dev.loghero.io.txt';
    }

    public function tearDown() {
        if(file_exists($this->devSettingsFilename)) {
            unlink($this->devSettingsFilename);
        }
    }

    public function testProvideDefaultSettings() {
        $settings = new LogHeroAPISettings($this->devSettingsFilename);
        static::assertEquals('https://api.loghero.io/logs/', $settings->getAPILogPackageEndpoint());
    }

    public function testProvideCustomizedSettings() {
        $settings = new LogHeroAPISettings($this->devSettingsFilename);
        $settings->setAPILogPackageEndpoint('https://test.loghero.io/logs/');
        static::assertEquals('https://test.loghero.io/logs/', $settings->getAPILogPackageEndpoint());
    }
}