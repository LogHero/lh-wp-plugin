<?php
namespace LogHero\Wordpress;
use \LogHero\Wordpress\LogHero_Plugin;
use \LogHero\Wordpress\LogHeroGlobals;


class LogHeroAdmin {
    public static $settingsGroup = 'loghero';
    public static $apiKeyOptionName = 'api_key';
    public static $useSyncTransportOptionName = 'use_sync_transport';
    public static $useSyncTransportOptionLabel = 'Disable Async Mode';

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
            static::$apiKeyOptionName,
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
            static::$useSyncTransportOptionName,
            static::$useSyncTransportOptionLabel,
            '\LogHero\Wordpress\LogHeroAdmin::useSyncTransportInputRenderer',
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
        <input type="text" name="api_key" id="api_key" value="<?php echo get_option(static::$apiKeyOptionName); ?>" />
        <?php
    }

    public static function useSyncTransportInputRenderer() {
        ?>
        <input name="use_sync_transport" id="use_sync_transport" type="checkbox" value="1" class="code" <?php echo checked( 1, get_option( static::$useSyncTransportOptionName ), false ) ?> />
        If enabled, the log events are sent synchronously to the LogHero API.
        Use this option only if you are having trouble with the async mode.
        <?php
    }

    public static function setupAdminNotices(){
        $currentApiKey = get_option(static::$apiKeyOptionName);
        if (!$currentApiKey) {
            echo '<div class="notice notice-warning is-dismissible">
                 <p>Your LogHero API key is not setup. Please go to the <a href="/wp-admin/options-general.php?page=loghero">LogHero settings page</a> and enter the API key retrieved from <a target="_blank" href="https://log-hero.com">log-hero.com</a>.</p>
             </div>';
        }
        $asyncFlushError = LogHeroGlobals::Instance()->errors()->getError('async-flush');
        if ($asyncFlushError) {
            echo '<div class="notice notice-warning is-dismissible">
                 <p>LogHero asynchronous flush failed! This is most likely caused by your server configuration which might block requests made from your backend.
                 The log events are currently flushed synchronously.
                 To suppress this warning, either update your server configuration or go to the <a href="/wp-admin/options-general.php?page=loghero">LogHero settings page</a> and activate the "' . static::$useSyncTransportOptionLabel . '" option.
                 For more information visit <a target="_blank" href="https://log-hero.com/issues/async-flush-failed">log-hero.com/issues/async-flush-failed</a>.</p>
                 <p> Error message: ' . $asyncFlushError . '</p>
             </div>';
        }
    }

    private static function flushSettingsToFiles() {
        LogHero_Plugin::refreshAPISettings();
        LogHeroGlobals::Instance()->refreshAPIKey(get_option(static::$apiKeyOptionName));
        $useSyncTransport = get_option(static::$useSyncTransportOptionName);
        if ($useSyncTransport) {
            LogHeroGlobals::Instance()->errors()->resolveError('async-flush');
        }
    }

}

LogHeroAdmin::setup();
require_once __DIR__ . '/loghero-admin-dev.php';
