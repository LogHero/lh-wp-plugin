<?php

namespace LogHero\Wordpress;


class LogHeroSettings {
    public static $clientId = 'Wordpress Plugin loghero/wp@0.2.0';
    public static $logEventsBufferFile = __DIR__ . '/logs/buffer.loghero.io.txt';
    public static $apiKeyStorageFile = __DIR__ . '/logs/key.loghero.io.txt';
}