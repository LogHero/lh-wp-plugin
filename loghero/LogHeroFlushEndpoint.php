<?php

namespace LogHero\Wordpress;

if (!defined('ABSPATH')) {
    exit;
}


class LogHeroFlushEndpoint {
    public static $restNamespace = 'loghero/v1';
    public static $restRoute = '/flush';

    public static function setup() {
        add_action('rest_api_init', '\LogHero\Wordpress\LogHeroFlushEndpoint::registerRoute');
    }

    public static function registerRoute() {
        register_rest_route(static::$restNamespace, static::$restRoute, array(
            'methods' => 'GET',
            'callback' => '\LogHero\Wordpress\LogHeroFlushEndpoint::handleFlush',
            'permission_callback' => '\LogHero\Wordpress\LogHeroFlushEndpoint::checkToken'
        ));
    }

    public static function url() {
        return rest_url(static::$restNamespace . static::$restRoute);
    }

    /**
     * The flush is triggered by the site itself, not by a logged in user, so it
     * authenticates with the API key sent in the Token header. Compared in constant
     * time to keep the comparison from leaking the key.
     */
    public static function checkToken($request) {
        $token = $request->get_header('token');
        if (!$token) {
            return false;
        }
        $settings = new LogHeroPluginSettings(LogHeroPluginClient::createSettingsStorage());
        $apiKey = $settings->getApiKey();
        if (!$apiKey) {
            return false;
        }
        return hash_equals($apiKey, $token);
    }

    public static function handleFlush($request) {
        try {
            $logHeroClient = new LogHeroPluginClient();
            $logHeroClient->flush($request->get_header('token'));
        }
        catch (\Exception $e) {
            LogHeroGlobals::Instance()->errors()->writeError('async-flush', $e);
            return new \WP_Error(
                'loghero_flush_failed',
                $e->getMessage(),
                array('status' => 500)
            );
        }
        return new \WP_REST_Response(null, 204);
    }

}
