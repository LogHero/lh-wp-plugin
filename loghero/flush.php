<?php

require_once '../../../wp-config.php';
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

$logHero = \LogHero\Wordpress\LogHeroClient_Plugin::getInstance();
$logHero->flush($_SERVER['HTTP_TOKEN']);
