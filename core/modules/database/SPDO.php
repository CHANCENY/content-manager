<?php

namespace Simp\Core\modules\database;

use Simp\Core\lib\installation\SystemDirectory;

class SPDO extends \PDO
{
    protected SystemDirectory $systemDirectory;
    public function __construct($dsn, $user, $pass) {
        $this->systemDirectory = new SystemDirectory();
        parent::__construct($dsn, $user, $pass);
    }

    public function log(string $message): bool|int
    {
        $file = $this->systemDirectory->setting_dir . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'database.log';
        return file_put_contents($file, $message, FILE_APPEND | LOCK_EX);
    }
}