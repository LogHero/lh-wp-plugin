<?php

namespace LogHero\Wordpress;

if (!defined('ABSPATH')) {
    exit;
}


/**
 * WP-Cron events that send the log buffer.
 *
 * loghero_flush_buffer is scheduled once on demand when the buffer is due and
 * the response cannot be finished early. loghero_flush_buffer_recurring runs
 * every five minutes as a safety net, so nothing stays in the buffer for long
 * even if a shutdown flush was interrupted.
 */
class LogHeroCron {
    public static $recurringHook = 'loghero_flush_buffer_recurring';
    public static $recurrence = 'loghero_five_minutes';

    public static function setup() {
        add_filter('cron_schedules', array(static::class, 'addSchedule'));
        add_action(WordPressDeferredLogTransport::$flushCronHook, array(static::class, 'flushBuffer'));
        add_action(static::$recurringHook, array(static::class, 'flushBuffer'));
    }

    public static function addSchedule($schedules) {
        $schedules[static::$recurrence] = array(
            'interval' => 5 * MINUTE_IN_SECONDS,
            'display' => 'Every five minutes (LogHero)'
        );
        return $schedules;
    }

    public static function schedule() {
        if (!wp_next_scheduled(static::$recurringHook)) {
            wp_schedule_event(time() + MINUTE_IN_SECONDS, static::$recurrence, static::$recurringHook);
        }
    }

    public static function unschedule() {
        wp_clear_scheduled_hook(static::$recurringHook);
        wp_clear_scheduled_hook(WordPressDeferredLogTransport::$flushCronHook);
    }

    public static function flushBuffer() {
        try {
            $client = new LogHeroPluginClient();
            $client->flushBuffer();
            LogHeroGlobals::Instance()->errors()->resolveError('deferred-flush');
        }
        catch (\Exception $e) {
            try {
                LogHeroGlobals::Instance()->errors()->writeError('deferred-flush', $e->getMessage());
            }
            catch (\Exception $ignored) {
            }
        }
    }
}
