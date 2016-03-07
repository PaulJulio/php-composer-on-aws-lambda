<?php
namespace PaulJulio\PhpOnLambda;
// this is all generally horrible and is just for getting set up
// ToDo: make not horrible
set_time_limit(0);
$autoload = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'vendor', 'autoload.php']);
require_once(realpath($autoload));
$settings = implode(DIRECTORY_SEPARATOR, [__DIR__, 'Settings.php']);
require_once(realpath($settings));


$settingsSO = new \PaulJulio\SettingsIni\SettingsSO();
$settingsSO->addIniFileNamesFromPath(__DIR__);

$settings = \PaulJulio\PhpOnLambda\Settings::Factory($settingsSO);

date_default_timezone_set($settings->timezone);
// https://blogs.aws.amazon.com/php/post/TxMLFLE50WUAMR/Provision-an-Amazon-EC2-Instance-with-PHP
$sdk = new \Aws\Sdk($settings->getAwsConfig());
$ec2 = $sdk->createEc2();
try {
    $response = $ec2->deleteKeyPair(['KeyName' => $settings->pemname]);
} catch (\Exception $e) {
    // if it doesn't exist, that's fine
}
$response = $ec2->createKeyPair(['KeyName'=>$settings->pemname]);
$pem = $response->get('KeyMaterial');
$pempath = $settings->getPemPath();
$pemfile = fopen($pempath, 'w');
fwrite($pemfile, $pem);
fclose($pemfile);
chmod($pempath, 0600);

if ($settings->createsecgrp) {
    try {
        $response = $ec2->deleteSecurityGroup(['GroupName' => $settings->secgrp]);
    } catch (\Exception $e) {
        // if it doesn't exist, that's fine
    }
    try {
        $response = $ec2->createSecurityGroup(['GroupName' => $settings->secgrp, 'Description' => 'ssh access']);
        $response = $ec2->authorizeSecurityGroupIngress([
            'GroupName' => $settings->secgrp,
            'IpProtocol' => 'tcp',
            'FromPort' => 22,
            'ToPort' => 22,
            'CidrIp' => '0.0.0.0/0',
        ]);
    } catch (\Exception $e) {
        // if another instance is running with the security group, we can neither delete not create it
    }
}

$response = $ec2->runInstances($settings->getProvisionConfig());
$instances = $response->get('Instances');
$instanceId = $instances[0]['InstanceId'];

echo "Waiting for the instance to become available" . PHP_EOL;
$ec2->waitUntil('InstanceRunning', ['InstanceIds'=>[$instanceId]]);

$response = $ec2->describeInstances(['InstanceIds'=>[$instanceId]]);
$reservations = $response->get('Reservations');
$address = $reservations[0]['Instances'][0]['PublicDnsName'];
echo $address  . ' is coming online ' . PHP_EOL;

echo "Waiting for the subnet to become available" . PHP_EOL;
$ec2->waitUntil('SubnetAvailable', ['InstanceIds'=>[$instanceId]]);

// magic numbers
echo "Wait 40 seconds for ssh to come online";
for ($i = 0; $i <= 40; ++$i) {
    sleep(1);
    if ($i % 10 == 0) {
        echo '+';
    } else {
        echo '.';
    }
}

$key = new \phpseclib\Crypt\RSA();
$key->loadKey(file_get_contents($settings->getPemPath()), \phpseclib\Crypt\RSA::PUBLIC_FORMAT_PKCS1);
$retries = 5; // magic number
$wait = 10; // magic number
$result = null;
while (!$result && $retries-- > 0) {
    if (isset($result)) {
        sleep($wait);
    }
    echo "Attempting connection to $address" . PHP_EOL;
    // $ssh = new \phpseclib\Net\SSH2($address);
    $ssh = new \phpseclib\Net\SSH2('ec2-54-188-92-76.us-west-2.compute.amazonaws.com');
    $result = $ssh->login('ec2-user', $key);
}
$ansi = new \phpseclib\File\ANSI();
$ssh->setTimeout(2);
$ansi->appendString($ssh->read());
$ssh->write("sudo su \n");
$ansi->appendString($ssh->read());
$ssh->write("yum install git -y \n");
$ansi->appendString($ssh->read());
$ssh->write("exit \n");
$ansi->appendString($ssh->read());
echo $ansi->getScreen();
echo $ssh->exec('git clone ' . $settings->repo . ' phplambda');
