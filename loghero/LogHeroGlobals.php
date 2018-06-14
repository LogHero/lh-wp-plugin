<?php

namespace LogHero\Wordpress;


class LogHeroGlobals {
    private $logEventsBufferFile;
    private $apiKeyStorageFile;
    private static $Instance;

    private function __construct() {
        $this->logEventsBufferFile = __DIR__ . '/logs/buffer.loghero.io.txt';
        $this->apiKeyStorageFile = __DIR__ . '/logs/key.loghero.io.txt';
    }

    public static function Instance() {
        if (!self::$Instance) {
            self::$Instance = new self();
        }
        return self::$Instance;
    }

    public function getClientId() {
        return 'Wordpress Plugin loghero/wp@0.2.0';
    }

    public function setLogEventsBufferFilename($logEventsBufferFile) {
        $this->logEventsBufferFile = $logEventsBufferFile;
    }

    public function getLogEventsBufferFilename() {
        return $this->logEventsBufferFile;
    }

    public function setAPIKeyStorageFilename($apiKeyStorageFile) {
        $this->apiKeyStorageFile = $apiKeyStorageFile;
    }

    public function getAPIKeyStorageFilename() {
        return $this->apiKeyStorageFile;
    }
}