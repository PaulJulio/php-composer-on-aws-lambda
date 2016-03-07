<?php
namespace PaulJulio\PhpOnLambda;

/**
 * This doc block serves to document what settings have been passed in via the ini files.
 * It is also great for auto-complete in IDE's
 *
 * @property string ami
 * @property string region
 * @property string key
 * @property string secret
 * @property string pemname
 * @property string pempath
 * @property string secgrp
 * @property string machinetype
 * @property string timezone
 * @property string repo
 * @property string publicdns
 * @property bool pemrelative
 * @property bool createsecgrp
 *
 * @method static Settings Factory() Factory(\PaulJulio\SettingsIni\SettingsSO $so)
 */
class Settings extends \PaulJulio\SettingsIni\Settings{

    /**
     * @return string
     *
     * Resolves relative path if set up that way
     */
    public function getPemPath() {
        $pempath = $this->pempath;
        if ($this->pemrelative) {
            $pempath = __DIR__ . DIRECTORY_SEPARATOR . $pempath;
        }
        return $pempath;
    }

    public function getAwsConfig() {
        return [
            'version' => 'latest',
            'region' => $this->region,
            'credentials' => [
                'key' => $this->key,
                'secret' => $this->secret,
            ],
        ];
    }

    public function getProvisionConfig() {
        return [
            'ImageId' => $this->ami,
            'MinCount' => 1,
            'MaxCount' => 1,
            'InstanceType' => $this->machinetype,
            'KeyName' => $this->pemname,
            'SecurityGroups' => [$this->secgrp],
        ];
    }

    public function setPublicDns($address) {
        $this->publicdns = $address;
    }
}
