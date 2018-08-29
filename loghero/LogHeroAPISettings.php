<?php
namespace LogHero\Wordpress;
use \LogHero\Client\APISettingsDefault;


class LogHeroAPISettings extends APISettingsDefault {
    private $pluginSettings;

    public function __construct($pluginSettings) {
        $this->pluginSettings = $pluginSettings;
    }

    public function getAPILogPackageEndpoint() {
        $customEndpoint = $this->pluginSettings->getApiEndpoint();
        if ($customEndpoint) {
            return $customEndpoint;
        }
        return parent::getAPILogPackageEndpoint();
    }
}
