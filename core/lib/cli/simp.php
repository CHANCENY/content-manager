<?php

$root = getcwd();

$vendor = $root.'/vendor/autoload.php';

if (!file_exists($vendor)) {
    die("run this script on root directory of your project");
}

require_once $vendor;

@system("cls");
@system('clear');

echo "Simp content management CLI".PHP_EOL;
echo implode("",array_fill(0, strlen("Simp content management CLI".PHP_EOL),'_'));
echo PHP_EOL.PHP_EOL;

// Grab the passed arguments
$command_line = $argv[1] ?? null;
$arguments = array_slice($argv, 2, count($argv) - 2);
if (empty($command_line)) {
    die(PHP_EOL.'command is not set'.PHP_EOL);
}

$cli_manager = new CliManager();

$found_command = $cli_manager->$command_line();
if (!$found_command) {
    echo PHP_EOL."command not found".PHP_EOL;
    exit(1);
}

$found_command($arguments);