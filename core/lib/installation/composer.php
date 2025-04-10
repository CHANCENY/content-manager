<?php

use Simp\Core\lib\installation\SystemDirectory;

$root = getcwd();

$vendor = $root.'/vendor/autoload.php';

if (!file_exists($vendor)) {
    die("run this script on root directory of your project");
}

require_once $vendor;

$system = new SystemDirectory;

$simp_php_file = $root .DIRECTORY_SEPARATOR . 'core' . 
DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'cli' . 
DIRECTORY_SEPARATOR . 'simp.php';

if (file_exists($simp_php_file)) {
    $content = file_get_contents($simp_php_file);

    $vendor_simp = "#!/usr/bin/env php".PHP_EOL. $content;

    @touch($root.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'bin'. DIRECTORY_SEPARATOR. 'simp');
    @touch($root.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'bin'. DIRECTORY_SEPARATOR. 'simp.php');

    @file_put_contents($root.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'bin'. DIRECTORY_SEPARATOR. 'simp', $vendor_simp);
    @file_put_contents($root.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'bin'. DIRECTORY_SEPARATOR. 'simp.php', $content);
}