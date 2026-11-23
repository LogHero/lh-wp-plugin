<?php

if (!defined('ABSPATH')) {
    exit;
}


require_once __DIR__ . '/sdk/autoload.php';

spl_autoload_register(
    function($class) {
        static $classes = null;
        if ($classes === null) {
            $classes = array(
                'loghero\\wordpress\\loghero_plugin' => '/loghero.php',
                'loghero\\wordpress\\logheropluginclient' => '/LogHeroPluginClient.php',
                'loghero\\wordpress\\logheroglobals' => '/LogHeroGlobals.php',
                'loghero\\wordpress\\logheroapisettings' => '/LogHeroAPISettings.php',
                'loghero\\wordpress\\logheropluginsettings' => '/LogHeroPluginSettings.php',
                'loghero\\wordpress\\wordpressdeferredlogtransport' => '/WordPressDeferredLogTransport.php',
                'loghero\\wordpress\\logherocron' => '/LogHeroCron.php',
                'loghero\\wordpress\\logheroupgrade' => '/LogHeroUpgrade.php'
            );
        }
        $cn = strtolower($class);
        if (isset($classes[$cn])) {
            require __DIR__ . $classes[$cn];
        }
    }
);
