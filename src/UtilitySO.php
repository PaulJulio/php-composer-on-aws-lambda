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
     * @param Settings $settings
     */
    public function setSettings(Settings $settings) {
        $this->settings = $settings;
    }

    /**
     * @return \PaulJulio\PhpOnLambda\Settings
     */
    public function getSettings() {
        return $this->settings;
    }
}