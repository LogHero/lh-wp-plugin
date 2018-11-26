<?php

namespace LogHero\Wordpress;
use \LogHero\Client\LogHeroErrors;


class LogHeroGlobals {
    private $logEventsBufferFile;
    private $settingsStorageFile;
    private $errors;
    private static $Instance;

    private function __construct() {
        $this->logEventsBufferFile = __DIR__ . '/logs/buffer.loghero.io.txt';
        $this->settingsStorageFile = __DIR__ . '/logs/settings.loghero.io.json';
        $this->errors = new LogHeroErrors(__DIR__ . '/logs/errors.loghero.io');
    }

    public static function Instance() {
        if (!self::$Instance) {
            self::$Instance = new self();
        }
        return self::$Instance;
    }

    public function getClientId() {
        return 'Wordpress Plugin loghero/wp@0.2.5';
    }

    public function setLogEventsBufferFilename($logEventsBufferFile) {
        $this->logEventsBufferFile = $logEventsBufferFile;
    }

    public function getLogEventsBufferFilename() {
        return $this->logEventsBufferFile;
    }

    public function setSettingsStorageFilename($settingsStorageFile) {
        $this->settingsStorageFile = $settingsStorageFile;
    }

    public function getSettingsStorageFilename() {
        return $this->settingsStorageFile;
    }

    public function errors() {
        return $this->errors;
    }

}
