<?php

namespace LogHero\Wordpress;


class LogHeroSettings {
    public $clientId;
    public $logEventsBufferFile;
    public $apiKeyStorageFile;

    public function __construct() {
        $this->clientId = 'Wordpress Plugin loghero/wp@0.2.0';
        $this->logEventsBufferFile = __DIR__ . '/logs/buffer.loghero.io.txt';
        $this->apiKeyStorageFile = __DIR__ . '/logs/key.loghero.io.txt';
    }
}