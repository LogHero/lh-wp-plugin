<?php

namespace LogHero\Wordpress;

use LogHero\Client\APIAccessException;
use LogHero\Client\APIAccessInterface;
use LogHero\Client\AsyncLogTransport;
use LogHero\Client\LogBufferInterface;

if (!defined('ABSPATH')) {
    exit;
}


/**
 * Triggers the asynchronous flush through the WordPress HTTP API instead of the
 * SDK's raw curl client.
 *
 * The SDK trigger sets no timeout and no CURLOPT_RETURNTRANSFER, so it blocked
 * the visitor's request until the flush endpoint answered, and wrote whatever
 * that endpoint printed straight into the visitor's page. A non blocking request
 * removes both problems and respects the proxy and filter settings of the site.
 */
class WordPressAsyncLogTransport extends AsyncLogTransport {
    private $flushEndpointUrl;
    private $flushToken;
    private $clientUserAgent;

    public function __construct(
        LogBufferInterface $logBuffer,
        APIAccessInterface $apiAccess,
        $clientId,
        $authorizationToken,
        $triggerEndpoint
    ) {
        parent::__construct($logBuffer, $apiAccess, $clientId, $authorizationToken, $triggerEndpoint);
        $this->flushEndpointUrl = $triggerEndpoint;
        $this->flushToken = $authorizationToken;
        $this->clientUserAgent = $clientId;
    }

    protected function triggerAsyncFlush() {
        # Resolved here rather than in the constructor: rest_url() needs $wp_rewrite,
        # which does not exist yet while plugins are being loaded. The trigger runs
        # on shutdown, by which time it does.
        if (!$this->flushEndpointUrl) {
            $this->flushEndpointUrl = LogHeroFlushEndpoint::url();
        }
        $response = wp_remote_get($this->flushEndpointUrl, array(
            'blocking' => false,
            'timeout' => 0.01,
            'redirection' => 0,
            'headers' => array(
                'Token' => $this->flushToken
            ),
            'user-agent' => $this->clientUserAgent
        ));
        if (is_wp_error($response)) {
            throw new APIAccessException(
                'Call to URL ' . $this->flushEndpointUrl . ' failed; Message: ' . $response->get_error_message()
            );
        }
    }

}
