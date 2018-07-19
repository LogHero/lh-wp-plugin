<?php

namespace LogHero\Wordpress;
use \LogHero\Client\APIKeyFileStorage;


class LogHeroGlobals {
    private $logEventsBufferFile;
    private $apiKeyStorageFile;
    private $errorFilePrefix;
    private static $Instance;

    private function __construct() {
        $this->logEventsBufferFile = __DIR__ . '/logs/buffer.loghero.io.txt';
        $this->apiKeyStorageFile = __DIR__ . '/logs/key.loghero.io.txt';
        $this->errorFilePrefix = __DIR__ . '/logs/errors.loghero.io';
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

    public function refreshAPIKey($apiKey) {
        $apiKeyStorage = new APIKeyFileStorage($this->getAPIKeyStorageFilename());
        $apiKeyStorage->setKey($apiKey);
    }

    public function getErrorFilename($errorTypeId) {
        return $this->errorFilePrefix . '.' . $errorTypeId . '.txt';
    }
}