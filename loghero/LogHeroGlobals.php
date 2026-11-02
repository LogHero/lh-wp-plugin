<?php

namespace LogHero\Wordpress;
use \LogHero\Client\LogHeroErrors;
use \LogHero\Client\PermissionDeniedException;

if (!defined('ABSPATH')) {
    exit;
}



class LogHeroGlobals {
    public static $storageKeyOptionName = 'loghero_storage_key';

    private $storageDirectory;
    private $logEventsBufferFile;
    private $errors;
    private static $Instance;

    private function __construct() {
    }

    public static function Instance() {
        if (!self::$Instance) {
            self::$Instance = new self();
        }
        return self::$Instance;
    }

    public function getClientId() {
        return 'WordPress Plugin loghero/wp@0.4.0';
    }

    /**
     * Directory for the file log buffer and the error files.
     *
     * Up to 0.3.0 these files lived in the plugin's own logs folder and were only
     * protected by an .htaccess file, which nginx and many other servers ignore.
     * They are now kept in a directory with an unguessable name below uploads,
     * or wherever LOGHERO_STORAGE_DIR / the loghero_storage_dir filter point to.
     * Site owners who can should point it outside the web root.
     */
    public function getStorageDirectory() {
        if ($this->storageDirectory === null) {
            $this->storageDirectory = static::resolveStorageDirectory();
            static::prepareStorageDirectory($this->storageDirectory);
        }
        return $this->storageDirectory;
    }

    public function setStorageDirectory($storageDirectory) {
        $this->storageDirectory = untrailingslashit($storageDirectory);
        static::prepareStorageDirectory($this->storageDirectory);
        $this->logEventsBufferFile = null;
        $this->errors = null;
    }

    public function setLogEventsBufferFilename($logEventsBufferFile) {
        $this->logEventsBufferFile = $logEventsBufferFile;
    }

    public function getLogEventsBufferFilename() {
        if ($this->logEventsBufferFile === null) {
            $this->logEventsBufferFile = $this->getStorageDirectory() . '/buffer-' . static::storageKey() . '.txt';
        }
        return $this->logEventsBufferFile;
    }

    public function errors() {
        if ($this->errors === null) {
            $this->errors = new LogHeroErrors($this->getStorageDirectory() . '/errors-' . static::storageKey());
        }
        return $this->errors;
    }

    /**
     * Folder used by version 0.3.0 and older. Everything in it was publicly
     * readable on servers that ignore .htaccess, see LogHeroUpgrade.
     */
    public static function legacyLogsDirectory() {
        return __DIR__ . '/logs';
    }

    private static function resolveStorageDirectory() {
        if (defined('LOGHERO_STORAGE_DIR') && LOGHERO_STORAGE_DIR) {
            $directory = LOGHERO_STORAGE_DIR;
        }
        else {
            $uploads = wp_upload_dir(null, false);
            $directory = trailingslashit($uploads['basedir']) . 'loghero-' . static::storageKey();
        }
        $directory = apply_filters('loghero_storage_dir', $directory);
        return untrailingslashit($directory);
    }

    private static function storageKey() {
        $key = get_option(static::$storageKeyOptionName);
        if (!$key || !preg_match('/^[a-zA-Z0-9]{32}$/', $key)) {
            # wp_generate_password() is pluggable and not defined while plugins load.
            $key = bin2hex(random_bytes(16));
            update_option(static::$storageKeyOptionName, $key, false);
            # Never create a folder for a key that was not persisted (database not
            # ready yet, e.g. during installation). It would be orphaned.
            if (get_option(static::$storageKeyOptionName) !== $key) {
                throw new PermissionDeniedException('Could not store the LogHero storage key in the database');
            }
        }
        return $key;
    }

    private static function prepareStorageDirectory($directory) {
        if (!is_dir($directory)) {
            wp_mkdir_p($directory);
        }
        if (!is_dir($directory) || !is_writable($directory)) {
            return;
        }
        # Defence in depth only. The unguessable directory and file names are what
        # protect the files on servers that ignore .htaccess.
        $guards = array(
            'index.php' => "<?php\n// Silence is golden.\n",
            '.htaccess' => "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n",
            'web.config' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<configuration><system.webServer><authorization><deny users=\"*\" /></authorization></system.webServer></configuration>\n"
        );
        foreach ($guards as $filename => $content) {
            $path = $directory . '/' . $filename;
            if (!file_exists($path)) {
                @file_put_contents($path, $content);
            }
        }
    }

}
