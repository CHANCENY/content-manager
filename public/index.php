<?php
@session_start();

require_once __DIR__ . '/../vendor/autoload.php';

use Simp\Core\lib\app\App;

App::runApp();