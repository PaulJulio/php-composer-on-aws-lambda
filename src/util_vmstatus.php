#!/usr/bin/php
<?php
require_once(realpath(implode(DIRECTORY_SEPARATOR,[__DIR__, 'util_common.php'])));
/* @var \PaulJulio\PhpOnLambda\Utility $utility */
$utility = getUtility();

// uncomment for raw status output
// $result = $utility->describeInstanceStatus();
// var_export($result);

$status = 'is';
if (!$utility->isInstanceRunning()) {
    $status .= ' not';
}
printf("The instance %s running" . PHP_EOL, $status);
