#!/usr/bin/php
<?php
if (file_exists(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'vendor', 'autoload']))) {
    // more common scenario where this is a dependency of another project
    require_once(realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', 'vendor', 'autoload.php'])));
} else {
    // less common scenario where this is the main project
    require_once(realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'vendor', 'autoload.php'])));
}

function getUtility() {
    static $utility = null;
    if (!isset($utility)) {
        // settings
        $sso = new \PaulJulio\SettingsIni\SettingsSO();
        $sso->addIniFileNamesFromPath(__DIR__);
        $settings = \PaulJulio\PhpOnLambda\Settings::Factory($sso);
        date_default_timezone_set($settings->timezone);
        // utility
        $uso = new \PaulJulio\PhpOnLambda\UtilitySO();
        $uso->setSettings($settings);
        $utility = \PaulJulio\PhpOnLambda\Utility::Factory($uso);
    }
    return $utility;
}
