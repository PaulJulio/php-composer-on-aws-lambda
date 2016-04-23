#!/usr/bin/php
<?php
require_once(realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'vendor', 'autoload.php'])));

$sso = new \PaulJulio\SettingsIni\SettingsSO();
$sso->addIniFileNamesFromPath(__DIR__);
$settings = \PaulJulio\PhpOnLambda\Settings::Factory($sso);

$cmd = strtr('ssh ec2-user@{address} -i {pempath} -o StrictHostKeyChecking=no', [
    '{address}' => $settings->publicdns,
    '{pempath}' => $settings->getPemPath(),
]);
passthru($cmd);
