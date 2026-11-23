<?php

namespace LogHero\Wordpress;

use LogHero\Client\LogTransport;

if (!defined('ABSPATH')) {
    exit;
}


/**
 * Sends buffered log events without making the visitor wait and without any
 * publicly reachable flush endpoint.
 *
 * Up to 0.3.0 the async mode triggered an HTTP request to the site's own REST
 * route loghero/v1/flush, authenticated with the LogHero API key. That route and
 * the settings file it depended on are gone. Instead:
 *
 * 1. On PHP-FPM and LiteSpeed the response is finished first, then the buffer is
 *    sent at the very end of the same PHP process.
 * 2. Without such a server API a WP-Cron event sends the buffer. A recurring
 *    WP-Cron event acts as a safety net in both cases.
 * 3. If WP-Cron is disabled and evidently not run by a system cron either, the
 *    buffer is sent synchronously, which was the previous fallback as well.
 */
class WordPressDeferredLogTransport extends LogTransport {
    public static $flushCronHook = 'loghero_flush_buffer';

    private $flushScheduled = false;

    public function flush() {
        if ($this->flushScheduled) {
            return;
        }
        if (static::canFinishRequest()) {
            $this->flushScheduled = true;
            register_shutdown_function(array($this, 'finishRequestAndFlush'));
            return;
        }
        if (static::wpCronIsRunning()) {
            $this->flushScheduled = true;
            if (!wp_next_scheduled(static::$flushCronHook)) {
                wp_schedule_single_event(time(), static::$flushCronHook);
            }
            return;
        }
        $this->flushNow();
    }

    public function flushNow() {
        parent::flush();
    }

    public function finishRequestAndFlush() {
        try {
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }
            else if (function_exists('litespeed_finish_request')) {
                litespeed_finish_request();
            }
            $this->flushNow();
        }
        catch (\Exception $e) {
            try {
                LogHeroGlobals::Instance()->errors()->writeError('deferred-flush', $e->getMessage());
            }
            catch (\Exception $ignored) {
            }
        }
    }

    private static function canFinishRequest() {
        return function_exists('fastcgi_finish_request') || function_exists('litespeed_finish_request');
    }

    /**
     * With DISABLE_WP_CRON a system cron usually calls wp-cron.php. If our
     * recurring event is overdue by more than 15 minutes, nobody does, and
     * relying on cron would let the buffer fill up.
     */
    private static function wpCronIsRunning() {
        if (!(defined('DISABLE_WP_CRON') && DISABLE_WP_CRON)) {
            return true;
        }
        $nextRun = wp_next_scheduled(LogHeroCron::$recurringHook);
        return $nextRun && $nextRun > time() - 15 * MINUTE_IN_SECONDS;
    }
}
