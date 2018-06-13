<?php

require_once __DIR__ . '/autoload.php';

ignore_user_abort( true );
set_time_limit(0);

ob_start();
echo $response;
header('Connection: close');
header('Content-Length: '.ob_get_length());
header('Content-Encoding: none');
ob_end_flush();
ob_flush();
flush();

if(session_id()) {
    session_write_close();
}

$apiKeyStorage = new \LogHero\Client\APIKeyFileStorage(__DIR__ . '/logs/key.loghero.io.txt');
$logEventFactory = new \LogHero\Client\LogEventFactory();
$logBuffer = new \LogHero\Client\FileLogBuffer(__DIR__ . '/logs/buffer.loghero.io.txt');
$apiAccess = new \LogHero\Client\APIAccess($apiKeyStorage, 'CLIENT ID');
$logTransport = new \LogHero\Client\LogTransport(
    $logBuffer,
    $apiAccess
);
$logTransport->flush();
