<?php
ini_set('memory_limit', '-1');

error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);
@session_start();

require_once __DIR__ . '/../vendor/autoload.php';

use Simp\Core\lib\app\App;

App::runApp();