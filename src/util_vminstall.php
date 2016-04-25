#!/usr/bin/php
<?php
require_once(realpath(implode(DIRECTORY_SEPARATOR,[__DIR__, 'util_common.php'])));
/* @var \PaulJulio\PhpOnLambda\Utility $utility */
$utility = getUtility();

if (!$utility->isInstanceRunning()) {
    print('Instance is not running, nothing to do.' . PHP_EOL);
    exit(1);
}

print('Installing git on instance' . PHP_EOL);
$utility->remoteInstallGit();
print('Cloning repo on instance' . PHP_EOL);
$utility->remoteCloneRepo();
print('Compiling php on remote machine' . PHP_EOL);
$utility->remoteCompilePhp();