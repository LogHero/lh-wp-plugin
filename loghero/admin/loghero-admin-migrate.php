<?php
namespace LogHero\Wordpress;

function loghero_migrate_option($fromOptionName, $toOptionName) {
    $currentValue = get_option($toOptionName);
    if (!$currentValue) {
        $previousValue = get_option($fromOptionName);
        if ($previousValue) {
            update_option($toOptionName, $previousValue);
            update_option($fromOptionName, null);
        }
    }
}

loghero_migrate_option('api_key', 'loghero_api_key');
loghero_migrate_option('api_endpoint', 'loghero_api_endpoint');
loghero_migrate_option('use_sync_transport', 'loghero_use_sync_transport');
