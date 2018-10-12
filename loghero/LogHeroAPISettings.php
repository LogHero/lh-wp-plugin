<?php
namespace LogHero\Wordpress;
use \LogHero\Client\APISettingsDefault;


class LogHeroAPISettings extends APISettingsDefault {
    private $pluginSettings;

    public function __construct(LogHeroPluginSettings $pluginSettings) {
        $this->pluginSettings = $pluginSettings;
    }

    public function getKey() {
        return $this->pluginSettings->getApiKey();
    }

    public function getLogPackageEndpoint() {
        $customEndpoint = $this->pluginSettings->getApiEndpoint();
        if ($customEndpoint) {
            return $customEndpoint;
        }
        return parent::getLogPackageEndpoint();
    }
}
