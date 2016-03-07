<?php
namespace PaulJulio\PhpOnLambda;

class UtilitySO {

    private $settings;

    public function isValid() {
        if (!isset($this->settings)) {
            return false;
        }
        return true;
    }

    /**
     * @param \PaulJulio\SettingsIni\SettingsSO $settings
     */
    public function setSettings(\PaulJulio\SettingsIni\SettingsSO $settings) {
        $this->settings = $settings;
    }

    /**
     * @return \PaulJulio\PhpOnLambda\Settings
     */
    public function getSettings() {
        return $this->settings;
    }
}