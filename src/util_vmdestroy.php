#!/usr/bin/php
<?php
require_once(realpath(implode(DIRECTORY_SEPARATOR,[__DIR__, 'util_common.php'])));
/* @var \PaulJulio\PhpOnLambda\Utility $utility */
$utility = getUtility();

if (!$utility->isInstanceRunning()) {
    print('Instance is not running, nothing to do.' . PHP_EOL);
    exit(0);
}

print("Attempting to terminate instance" . PHP_EOL);
$result = $utility->terminateInstance(false);

$status = 'is';
if (!$utility->isInstanceRunning()) {
    $status .= ' not';
}
printf("The instance %s running" . PHP_EOL, $status);
