<?php

namespace LogHero\Wordpress;

if (!defined('ABSPATH')) {
    exit;
}


/**
 * One time migration to 0.4.0.
 *
 * Versions up to 0.3.0 wrote the settings (including the API key), the log
 * buffer (including visitor IP addresses) and error messages into the plugin's
 * logs folder, protected only by an .htaccess file. On nginx and other servers
 * that ignore .htaccess these files were publicly readable. WordPress replaces
 * the plugin folder on a regular update, but not on manual uploads via FTP or
 * deployments that copy over the old folder, so the files are removed here.
 * Pending log events are carried over into the new buffer first.
 */
class LogHeroUpgrade {
    public static $versionOptionName = 'loghero_plugin_version';
    public static $currentVersion = '0.4.0';

    public static function setup() {
        add_action('init', array(static::class, 'maybeUpgrade'), 1);
    }

    public static function maybeUpgrade() {
        if (get_option(static::$versionOptionName) === static::$currentVersion) {
            return;
        }
        static::upgrade();
    }

    public static function upgrade() {
        static::migrateLegacyBuffer();
        static::removeLegacyFiles();
        LogHeroCron::schedule();
        update_option(static::$versionOptionName, static::$currentVersion);
    }

    public static function activate() {
        LogHeroCron::setup();
        static::upgrade();
    }

    public static function deactivate() {
        LogHeroCron::unschedule();
    }

    private static function migrateLegacyBuffer() {
        $legacyBuffer = LogHeroGlobals::legacyLogsDirectory() . '/buffer.loghero.io.txt';
        if (!is_readable($legacyBuffer) || filesize($legacyBuffer) === 0) {
            return;
        }
        try {
            $newBuffer = LogHeroGlobals::Instance()->getLogEventsBufferFilename();
            if (!is_writable(dirname($newBuffer))) {
                return;
            }
            $content = file_get_contents($legacyBuffer);
            if ($content) {
                file_put_contents($newBuffer, $content, FILE_APPEND | LOCK_EX);
            }
        }
        catch (\Exception $e) {
        }
    }

    private static function removeLegacyFiles() {
        $directory = LogHeroGlobals::legacyLogsDirectory();
        if (!is_dir($directory)) {
            return;
        }
        $patterns = array(
            'settings.loghero.io.json',
            'buffer.loghero.io.txt',
            'buffer.loghero.io.last-dump.timestamp',
            'errors.loghero.io.*'
        );
        foreach ($patterns as $pattern) {
            $files = glob($directory . '/' . $pattern);
            if (!$files) {
                continue;
            }
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }
        foreach (array('.htaccess', '.gitkeep', 'index.php') as $leftover) {
            if (is_file($directory . '/' . $leftover)) {
                @unlink($directory . '/' . $leftover);
            }
        }
        @rmdir($directory);
    }
}
