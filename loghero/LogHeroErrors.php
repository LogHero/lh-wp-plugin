<?php

namespace LogHero\Wordpress;


class LogHeroErrors {
    private $errorFilePrefix;

    public function __construct($errorFilePrefix) {
        $this->errorFilePrefix = $errorFilePrefix;
    }

    public function setErrorFilenamePrefix($errorFilePrefix) {
        $this->errorFilePrefix = $errorFilePrefix;
    }

    public function writeError($errorTypeId, $fullError) {
        $errorFilename = $this->getErrorFilename($errorTypeId);
        file_put_contents($errorFilename, $fullError);
        chmod($errorFilename, 0666);
    }

    public function getError($errorTypeId) {
        $asyncFlushErrorFile = $this->getErrorFilename($errorTypeId);
        if (file_exists($asyncFlushErrorFile)) {
            return fgets(fopen($asyncFlushErrorFile, 'r'));
        }
        return null;
    }

    private function getErrorFilename($errorTypeId) {
        return $this->errorFilePrefix . '.' . $errorTypeId . '.txt';
    }
}