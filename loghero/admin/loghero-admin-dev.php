<?php
use LogHero\Wordpress\LogHeroPluginSettings;

function loghero_dev_options_page()
{
    ?>
    <div class="wrap">
        <div id="icon-options-general" class="icon32"></div>
        <h1>LogHero Options</h1>
        <form method="post" action="options.php">
            <?php

            //add_settings_section callback is displayed here. For every new section we need to call settings_fields.
            settings_fields('loghero-dev');

            // all the add_settings_field callbacks is displayed here
            do_settings_sections('loghero-dev');

            // Add the submit button to serialize the options
            submit_button();

            ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', 'loghero_dev_admin_init');
function loghero_dev_admin_init() {
    $settings_group = 'loghero-dev';
    $setting_name = 'api_endpoint';

    $settings_section = 'loghero_dev';
    $page = $settings_group;
    add_settings_section(
        $settings_section,
        'Developer Settings',
        '',
        $page
    );

    // Add fields to that section
    add_settings_field(
        $setting_name,
        'Log API Endpoint',
        'loghero_api_endpoint_input_renderer',
        $page,
        $settings_section
    );

    register_setting($page, $setting_name);
}

add_action('admin_menu', 'loghero_dev_admin_add_page');
function loghero_dev_admin_add_page() {
    add_submenu_page(
        null,
        'LogHero Developer Settings',
        'LogHero Developer Settings',
        'manage_options',
        'loghero-dev',
        'loghero_dev_options_page'
    );
}


function loghero_api_endpoint_input_renderer() {
    ?>
    <input
        type="text"
        name="<?php echo LogHeroPluginSettings::$apiEndpointOptionName ?>"
        id="<?php echo LogHeroPluginSettings::$apiEndpointOptionName ?>"
        value="<?php echo get_option(LogHeroPluginSettings::$apiEndpointOptionName); ?>"
    />
    <?php
}
