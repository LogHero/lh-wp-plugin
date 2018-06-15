<?php
namespace LogHero\Wordpress;
use \LogHero\Client\APISettingsDefault;


class LogHeroAPISettings extends APISettingsDefault {
    private $apiLogPackageEndpoint;

    public function __construct($apiDevSettingsFile = null) {
        if (!$apiDevSettingsFile) {
            $apiDevSettingsFile = __DIR__ . '/logs/dev.loghero.io.txt';
        }
        $this->apiDevSettingsFile = $apiDevSettingsFile;
    }

    public function setAPILogPackageEndpoint($apiLogPackageEndpoint) {
        $this->apiLogPackageEndpoint = $apiLogPackageEndpoint;
    }

    public function getAPILogPackageEndpoint() {
        if ($this->apiLogPackageEndpoint) {
            return $this->apiLogPackageEndpoint;
        }
        return parent::getAPILogPackageEndpoint();
    }
}
