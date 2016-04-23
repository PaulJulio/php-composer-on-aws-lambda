#!/usr/bin/php
<?php
require_once(realpath(implode(DIRECTORY_SEPARATOR,[__DIR__, 'util_common.php'])));
/* @var \PaulJulio\PhpOnLambda\Utility $utility */
$utility = getUtility();

if ($utility->isInstanceRunning()) {
    print("Machine is running, nothing to do" . PHP_EOL);
    exit(0);
}
if (!$utility->keyPairExists()) {
    $result = $utility->createKeyPair();
    if (!isset($result)) {
        print("Unable to create key pair" . PHP_EOL);
        exit(1);
    }
    print("Key pair created" . PHP_EOL);
} else {
    print("Using existing key pair" . PHP_EOL);
}

// todo: use the describe logic to check for this
$result = $utility->createSecurityGroup();
if (!isset($result)) {
    print('Unable to create security group, ignoring and hoping it already exists' . PHP_EOL);
} else {
    print('Created security group' . PHP_EOL);
}
$result = $utility->runInstance(false);

