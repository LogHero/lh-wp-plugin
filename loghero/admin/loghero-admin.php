<?php
namespace LogHero\Wordpress;
use LogHero\Client\RedisOptions;
use \LogHero\Wordpress\LogHero_Plugin;
use \LogHero\Wordpress\LogHeroGlobals;
use \LogHero\Client\PermissionDeniedException;


class LogHeroAdmin {
    public static $settingsGroup = 'loghero';
    public static $useSyncTransportOptionLabel = 'Disable Async Mode';
    public static $disableTransportOptionLabel = 'Disable Log Transport';

    public static function setup() {
        add_action('admin_menu', '\LogHero\Wordpress\LogHeroAdmin::addLogHeroAdminPage');
        add_action('admin_init', '\LogHero\Wordpress\LogHeroAdmin::initAdmin');
        add_action('admin_notices', '\LogHero\Wordpress\LogHeroAdmin::setupAdminNotices');
        static::flushSettingsToFiles();
    }

    public static function addLogHeroAdminPage() {
        add_options_page(
            'LogHero Settings',
            'LogHero',
            'manage_options',
            static::$settingsGroup,
            '\LogHero\Wordpress\LogHeroAdmin::createLogHeroOptionsPage'
        );
    }

    public static function initAdmin() {
        static::addBasicSection();
        static::addAdvancedSection();
    }


    public static function createLogHeroOptionsPage() {
        ?>
        <div class="wrap">
            <div id="icon-options-general" class="icon32"></div>
            <h1>LogHero Options</h1>
            <form method="post" action="options.php">
                <?php

                //add_settings_section callback is displayed here. For every new section we need to call settings_fields.
                settings_fields(static::$settingsGroup);

                // all the add_settings_field callbacks is displayed here
                do_settings_sections(static::$settingsGroup);

                // Add the submit button to serialize the options
                submit_button();

                ?>
            </form>
        </div>
        <?php
    }

    public static function addBasicSection() {
        $settingsSection = 'loghero_basic';
        add_settings_section(
            $settingsSection,
            'Basic Setup',
            '', # TODO Add help text with this column
            static::$settingsGroup
        );
        static::addFieldToSection(
            LogHeroPluginSettings::$apiKeyOptionName,
            'LogHero API Key (required)',
            '\LogHero\Wordpress\LogHeroAdmin::apiKeyInputRenderer',
            static::$settingsGroup,
            $settingsSection
        );
    }

    public static function addAdvancedSection() {
        $settingsSection = 'loghero_advanced';
        add_settings_section(
            $settingsSection,
            'Advanced Setup',
            '', # TODO Add help text with this column
            static::$settingsGroup
        );
        static::addFieldToSection(
            LogHeroPluginSettings::$disableTransportOptionName,
            static::$disableTransportOptionLabel,
            '\LogHero\Wordpress\LogHeroAdmin::disableTransportInputRenderer',
            static::$settingsGroup,
            $settingsSection
        );
        static::addFieldToSection(
            LogHeroPluginSettings::$useSyncTransportOptionName,
            static::$useSyncTransportOptionLabel,
            '\LogHero\Wordpress\LogHeroAdmin::useSyncTransportInputRenderer',
            static::$settingsGroup,
            $settingsSection
        );
        static::addFieldToSection(
            LogHeroPluginSettings::$redisUrlOptionName,
            'Redis URL',
            '\LogHero\Wordpress\LogHeroAdmin::redisUrlInputRenderer',
            static::$settingsGroup,
            $settingsSection
        );
        static::addFieldToSection(
            LogHeroPluginSettings::$redisKeyPrefixOptionName,
            'Redis Key Prefix',
            '\LogHero\Wordpress\LogHeroAdmin::redisKeyPrefixInputRenderer',
            static::$settingsGroup,
            $settingsSection
        );
    }

    public static function addFieldToSection($fieldName, $fieldLabel, $inputRenderer, $settingsGroup, $settingsSection) {
        add_settings_field(
            $fieldName,
            $fieldLabel,
            $inputRenderer,
            $settingsGroup,
            $settingsSection
        );
        register_setting($settingsGroup, $fieldName); # TODO Use sanitize callback
    }

    public static function apiKeyInputRenderer() {
        ?>
        <input
            type="text"
            name="<?php echo LogHeroPluginSettings::$apiKeyOptionName ?>"
            id="<?php echo LogHeroPluginSettings::$apiKeyOptionName ?>"
            value="<?php echo get_option(LogHeroPluginSettings::$apiKeyOptionName); ?>"
        />
        <?php
    }

    public static function redisUrlInputRenderer() {
        ?>
        <input
            type="text"
            name="<?php echo LogHeroPluginSettings::$redisUrlOptionName ?>"
            id="<?php echo LogHeroPluginSettings::$redisUrlOptionName ?>"
            value="<?php echo get_option(LogHeroPluginSettings::$redisUrlOptionName); ?>"
        />
        <p class="description">Use Redis store to buffer log events.</p>
        <?php
    }

    public static function redisKeyPrefixInputRenderer() {
        ?>
        <input
            type="text"
            name="<?php echo LogHeroPluginSettings::$redisKeyPrefixOptionName ?>"
            id="<?php echo LogHeroPluginSettings::$redisKeyPrefixOptionName ?>"
            value="<?php echo get_option(LogHeroPluginSettings::$redisKeyPrefixOptionName); ?>"
        />
        <p class="description">Redis key used to store buffered log events (default: "<?php echo LogHeroPluginSettings::buildDefaultRedisKeyPrefix(get_option(LogHeroPluginSettings::$apiKeyOptionName)); ?>").</p>
        <?php
    }

    public static function useSyncTransportInputRenderer() {
        ?>
        <input
            name="<?php echo LogHeroPluginSettings::$useSyncTransportOptionName ?>"
            id="<?php echo LogHeroPluginSettings::$useSyncTransportOptionName ?>"
            type="checkbox"
            value="1"
            class="code"
            <?php echo checked( 1, get_option( LogHeroPluginSettings::$useSyncTransportOptionName ), false ) ?>
        />
        If enabled, log events are sent synchronously to the LogHero API.
        Use this option only if you are having trouble with the async mode.
        <?php
    }

    public static function disableTransportInputRenderer() {
        ?>
        <input
            name="<?php echo LogHeroPluginSettings::$disableTransportOptionName ?>"
            id="<?php echo LogHeroPluginSettings::$disableTransportOptionName ?>"
            type="checkbox"
            value="1"
            class="code"
            <?php echo checked( 1, get_option( LogHeroPluginSettings::$disableTransportOptionName ), false ) ?>
        />
        If enabled, log events are never sent to the LogHero API.
        Use this option if you want to submit the log events manually.
        <?php
    }

    public static function setupAdminNotices(){
        $currentApiKey = get_option(LogHeroPluginSettings::$apiKeyOptionName);
        if (!$currentApiKey) {
            echo '<div class="notice notice-warning is-dismissible">
                 <p>Your LogHero API key is not setup. Please go to the <a href="/wp-admin/options-general.php?page=loghero">LogHero settings page</a> and enter the API key retrieved from <a target="_blank" href="https://log-hero.com">log-hero.com</a>.</p>
             </div>';
        }
        try {
            $asyncFlushError = LogHeroGlobals::Instance()->errors()->getError('async-flush');
            if ($asyncFlushError) {
                echo '<div class="notice notice-warning is-dismissible">
                 <p>LogHero asynchronous flush failed! This is most likely caused by your server configuration which might block requests made from your backend.
                 The log events are currently flushed synchronously.
                 To suppress this warning, either update your server configuration or go to the <a href="/wp-admin/options-general.php?page=loghero">LogHero settings page</a> and activate the "' . static::$useSyncTransportOptionLabel . '" option.
                 For more information visit <a target="_blank" href="https://log-hero.com/docs/asynchronous-flush-failed/">https://log-hero.com/docs/asynchronous-flush-failed/</a>.</p>
                 <p>Error message: ' . $asyncFlushError . '</p>
             </div>';
            }
            $unexpectedError = LogHeroGlobals::Instance()->errors()->getError('unexpected');
            if ($unexpectedError) {
                echo '<div class="notice notice-warning is-dismissible">
                     <p>Your LogHero plugin does not work propery!</p>
                     <p>Error message: ' . $unexpectedError . '</p>
                 </div>';
                LogHeroGlobals::Instance()->errors()->resolveError('unexpected');
            }
        }
        catch(PermissionDeniedException $permissionDeniedError) {
            if (LogHeroPluginSettings::accessToLogsFolderIsRequired()) {
                echo '<div class="notice notice-error is-dismissible">
                 <p>Your LogHero plugin cannot write to the "logs" folder (permission denied). Please set access rights properly or use the Redis log buffer with synchronous flush.
                 For more information visit <a target="_blank" href="https://log-hero.com/docs/permission-denied">https://log-hero.com/docs/permission-denied</a>.</p>
                 <p>Error message: ' . $permissionDeniedError->getMessage() . '</p>
                </div>';
            }
        }
    }

    private static function flushSettingsToFiles() {
        try {
            LogHero_Plugin::refreshPluginSettings();
            $useSyncTransport = get_option(LogHeroPluginSettings::$useSyncTransportOptionName);
            if ($useSyncTransport) {
                LogHeroGlobals::Instance()->errors()->resolveError('async-flush');
            }
        }
        catch(PermissionDeniedException $e) {
        }
    }

}

# LOG-182
require_once __DIR__ . '/loghero-admin-migrate.php';


LogHeroAdmin::setup();
require_once __DIR__ . '/loghero-admin-dev.php';
