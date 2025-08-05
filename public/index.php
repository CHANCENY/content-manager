<?php

use Simp\Core\lib\installation\InstallerValidator;

@session_start();

require_once __DIR__ . '/../vendor/autoload.php';

// Example usage
$validator = new InstallerValidator();
$validator->bootApplication();