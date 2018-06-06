<?php

require_once __DIR__ . '/sdk/autoload.php';

spl_autoload_register(
    function($class) {
        static $classes = null;
        if ($classes === null) {
            $classes = array(
                'loghero\\wordpress\\invalidtokenexception' => '/InvalidTokenException.php',
                'loghero\\wordpress\\logheroclient_plugin' => '/loghero.php',
            );
        }
        $cn = strtolower($class);
        if (isset($classes[$cn])) {
            require __DIR__ . $classes[$cn];
        }
    }
);
