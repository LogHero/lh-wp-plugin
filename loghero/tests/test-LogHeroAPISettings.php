<?php
namespace LogHero\Wordpress;

class LogHeroAPISettingsTest extends \WP_UnitTestCase {
    private $devSettingsFilename;

    public function setUp() {
        $this->devSettingsFilename = __DIR__ . '/logs/dev.loghero.io.json';
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

    public function testPersistCustomizedSettingsInDevSettingsFile() {
        $settings = new LogHeroAPISettings($this->devSettingsFilename);
        $settings->setAPILogPackageEndpoint('https://test.loghero.io/logs/');
        $this->assertEndpointInDevSettingsFile('https://test.loghero.io/logs/');
        $settingsReinstantiated = new LogHeroAPISettings($this->devSettingsFilename);
        static::assertEquals('https://test.loghero.io/logs/', $settingsReinstantiated->getAPILogPackageEndpoint());
    }

    public function testHandleParseErrors() {
        file_put_contents($this->devSettingsFilename, 'NO_JSON');
        $settings = new LogHeroAPISettings($this->devSettingsFilename);
        static::assertEquals('https://api.loghero.io/logs/', $settings->getAPILogPackageEndpoint());
    }

    public function testHandleEmptyJson() {
        file_put_contents($this->devSettingsFilename, '{}');
        $settings = new LogHeroAPISettings($this->devSettingsFilename);
        static::assertEquals('https://api.loghero.io/logs/', $settings->getAPILogPackageEndpoint());
    }

    private function assertEndpointInDevSettingsFile($expectedEndpoint) {
        $jsonString = file_get_contents($this->devSettingsFilename);
        $json = json_decode($jsonString, true);
        static::assertEquals($json['apiLogPackageEndpoint'], $expectedEndpoint);
    }
}