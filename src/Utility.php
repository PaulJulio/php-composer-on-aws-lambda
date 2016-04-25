<?php
namespace PaulJulio\PhpOnLambda;
use \Exception;

class Utility {

    const SSH_DELAY = 40;
    const SSH_RETRIES = 5;
    const SSH_RETRY_DELAY = 10;
    const SSH_READ_TIMEOUT = 2;

    /* @var \PaulJulio\PhpOnLambda\UtilitySO */
    private $so;
    /* @var \Aws\Sdk */
    private $sdk;

    private function __construct(){}

    public static function Factory(UtilitySO $so) {
        if (!$so->isValid()) {
            throw new Exception('Invalid settings object');
        }
        $instance = new static;
        $instance->so = $so;
        $instance->sdk = new \Aws\Sdk($so->getSettings()->getAwsConfig());
        return $instance;
    }

    /**
     * @param bool $suppressException
     * @return \Aws\Result|null
     * @throws Exception
     */
    public function deleteKeyPair($suppressException = true) {
        $result = null;
        $ec2 = $this->sdk->createEc2();
        try {
            $result = $ec2->deleteKeyPair(['KeyName' => $this->so->getSettings()->pemname]);
        } catch (Exception $e) {
            if (!$suppressException) {
                throw $e;
            }
        }
        return $result;
    }

    /**
     * @param bool $suppressException
     * @return \Aws\Result|null
     * @throws Exception
     */
    public function createKeyPair($suppressException = true) {
        $result = null;
        $ec2 = $this->sdk->createEc2();
        try {
            $result = $ec2->createKeyPair(['KeyName' => $this->so->getSettings()->pemname]);
            $pem = $result->get('KeyMaterial');
            $pempath = $this->so->getSettings()->getPemPath();
            $pemfile = fopen($pempath, 'w');
            fwrite($pemfile, $pem);
            fclose($pemfile);
            chmod($pempath, 0600);
        } catch (Exception $e) {
            if (!$suppressException) {
                throw $e;
            }
        }
        return $result;
    }
    
    public function keyPairExists() {
        return file_exists($this->so->getSettings()->getPemPath());
    }

    /**
     * @param bool $suppressException
     * @return \Aws\Result|null
     * @throws Exception
     */
    public function deleteSecurityGroup($suppressException = true) {
        $result = null;
        $ec2 = $this->sdk->createEc2();
        try {
            $result = $ec2->deleteSecurityGroup(['GroupName' => $this->so->getSettings()->secgrp]);
        } catch (Exception $e) {
            if (!$suppressException) {
                throw $e;
            }
        }
        return $result;
    }

    /**
     * @param bool $suppressException
     * @return \Aws\Result|null
     * @throws Exception
     */
    public function createSecurityGroup($suppressException = true) {
        $result = null;
        $ec2 = $this->sdk->createEc2();
        try {
            $ec2->createSecurityGroup([
                'GroupName' => $this->so->getSettings()->secgrp,
                'Description' => 'ssh access'
            ]);
            $result = $ec2->authorizeSecurityGroupIngress([
                'GroupName' => $this->so->getSettings()->secgrp,
                'IpProtocol' => 'tcp',
                'FromPort' => 22,
                'ToPort' => 22,
                'CidrIp' => '0.0.0.0/0',
            ]);
        } catch (Exception $e) {
            if (!$suppressException) {
                throw $e;
            }
        }
        return $result;
    }

    /**
     * @return \phpseclib\Crypt\RSA
     */
    public function getKey($renew = false) {
        static $key = null;
        if ($renew || !isset($key)) {
            $key = new \phpseclib\Crypt\RSA();
            $key->loadKey(file_get_contents($this->so->getSettings()->getPemPath()), \phpseclib\Crypt\RSA::PUBLIC_FORMAT_PKCS1);
        }
        return $key;
    }

    public function getSSH($address) {
        $ssh = new \phpseclib\Net\SSH2($address);
        $result = $ssh->login('ec2-user', $this->getKey());
        if (!$result) {
            throw new Exception('Unable to establish ssh session');
        }
        return $ssh;
    }

    public function terminateInstance($suppressException = true) {
        $result = null;
        $ec2 = $this->sdk->createEc2();
        try {
            $result = $ec2->terminateInstances(['InstanceIds' => [$this->so->getSettings()->instanceid]]);
        } catch (Exception $e) {
            if (!$suppressException) {
                throw $e;
            }
        }
        return $result;
    }
    /**
     * @param bool $suppressException
     * @param bool $silent
     * @return bool true if able to establish an ssh connection
     * @throws Exception
     */
    public function runInstance($suppressException = true, $silent = false) {
        $result = null;
        $ec2 = $this->sdk->createEc2();
        try {
            $result = $ec2->runInstances($this->so->getSettings()->getProvisionConfig());
            $instances = $result->get('Instances');
            $instanceId = $instances[0]['InstanceId'];
            if (!$silent) {
                echo "Waiting for Instance to Become Available" . PHP_EOL;
            }
            $ec2->waitUntil('InstanceRunning', ['InstanceIds' => [$instanceId]]);
            $result = $ec2->describeInstances(['InstanceIds' => [$instanceId]]);
            $reservations = $result->get('Reservations');
            $address = $reservations[0]['Instances'][0]['PublicDnsName'];
            // record the address
            $fh = fopen('machine.ini', 'w');
            fwrite($fh, '[machine]' . PHP_EOL);
            fwrite($fh, 'publicdns = ' . $address . PHP_EOL);
            fwrite($fh, 'instanceid = ' . $instanceId . PHP_EOL);
            fclose($fh);
            // set the address in the settings as if it had been read from that ini file
            $this->so->getSettings()->setPublicDns($address);
            $this->so->getSettings()->setInstanceId($instanceId);
            if (!$silent) {
                echo "Watiing for the subnet to become available" . PHP_EOL;
            }
            $ec2->waitUntil('SubnetAvailable', ['InstanceIds' => [$instanceId]]);
            if (!$silent) {
                echo(sprintf('Waiting %s seconds for ssh to come online' . PHP_EOL, self::SSH_DELAY));
            }
            for ($i = 0; $i < self::SSH_DELAY; $i++) {
                sleep (1);
                if (!$silent) {
                    if ($i > 0 && $i % 10 == 0) {
                        echo '+';
                    } else {
                        echo '.';
                    }
                }
            }
            $result = null;
            $retries = self::SSH_RETRIES;
            while (!$result && $retries-- > 0) {
                if (isset($result)) {
                    sleep(self::SSH_RETRY_DELAY);
                }
                if (!$silent) {
                    echo "Attempting connection to $address " . PHP_EOL;
                }
                try {
                    $ssh = $this->getSSH($address);
                    if (!$silent) {
                        echo "Connection successful" . PHP_EOL;
                    }
                    $ssh->disconnect();
                    $result = true;
                } catch (Exception $e) {
                    $result = false;
                }
            }
        } catch (Exception $e) {
            if (!$suppressException) {
                throw $e;
            }
        }
        return $result;
    }

    /**
     * @return \Aws\Result|null
     */
    public function describeInstanceStatus() {
        $iid = $this->so->getSettings()->instanceid;
        if (!isset($iid)) {
            return null;
        }
        $ec2 = $this->sdk->createEc2();
        $result = $ec2->describeInstanceStatus(['InstanceIds' => [$iid]]);
        return $result;
    }
    
    public function isInstanceRunning() {
        try {
            $result = $this->describeInstanceStatus();
        } catch (Exception $e) {
            return false;
        }
        if (!isset($result)) {
            return false;
        }
        if (count($result['InstanceStatuses']) < 1) {
            return false;
        }
        if (in_array($result['InstanceStatuses'][0]['InstanceState']['Name'], ['running', 'pending'])) {
            return true;
        }
        return false;
    }

    /**
     * @param bool $silent
     * @throws Exception
     */
    public function remoteInstallGit($silent = false) {
        $ansi = new \phpseclib\File\ANSI();
        $ssh = $this->getSSH($this->so->getSettings()->publicdns);
        $ssh->setTimeout(self::SSH_READ_TIMEOUT);
        $ansi->appendString($ssh->read());
        $ssh->write("sudo su \n");
        $ansi->appendString($ssh->read());
        $ssh->write("yum install git -y \n");
        $ansi->appendString($ssh->read());
        $ssh->write("exit \n");
        $ansi->appendString($ssh->read());
        if (!$silent) {
            echo $ansi->getScreen();
        }
    }

    /**
     * @param bool $silent
     * @throws Exception
     */
    public function remoteCloneRepo($silent = false) {
        $ssh = $this->getSSH($this->so->getSettings()->publicdns);
        $output = $ssh->exec('git clone ' . $this->so->getSettings()->repo . ' phponlambda');
        if (!$silent) {
            echo $output;
        }
    }
    
    public function remoteCompilePhp($silent = false) {
        $ansi = new \phpseclib\File\ANSI();
        $ssh = $this->getSSH($this->so->getSettings()->publicdns);
        $ssh->setTimeout(self::SSH_READ_TIMEOUT);
        $ansi->appendString($ssh->read());
        $ssh->write("sudo su \n");
        $ansi->appendString($ssh->read());
        $ssh->write("sh ./phponlambda/src/remote_compile.sh \n");
        $ansi->appendString($ssh->read());
        $ssh->write("exit \n");
        $ansi->appendString($ssh->read());
        if (!$silent) {
            echo $ansi->getScreen();
        }
        
    }
}
