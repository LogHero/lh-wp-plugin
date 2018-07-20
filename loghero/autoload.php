<?php

require_once __DIR__ . '/sdk/autoload.php';

spl_autoload_register(
    function($class) {
        static $classes = null;
        if ($classes === null) {
            $classes = array(
                'loghero\\wordpress\\invalidtokenexception' => '/InvalidTokenException.php',
                'loghero\\wordpress\\loghero_plugin' => '/loghero.php',
                'loghero\\wordpress\\logheropluginclient' => '/LogHeroPluginClient.php',
                'loghero\\wordpress\\logheroglobals' => '/LogHeroGlobals.php',
                'loghero\\wordpress\\logheroapisettings' => '/LogHeroAPISettings.php',
                'loghero\\wordpress\\logheroerrors' => '/LogHeroErrors.php'
            );
        }
        $cn = strtolower($class);
        if (isset($classes[$cn])) {
            require __DIR__ . $classes[$cn];
        }
    }
);
