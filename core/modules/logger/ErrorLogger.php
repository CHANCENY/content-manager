<?php

namespace Simp\Core\modules\logger;

use Monolog\Logger;
use Simp\Core\lib\installation\SystemDirectory;

class ErrorLogger
{
    const LEVEL_INFO = 0;
    const LEVEL_WARNING = 1;
    const LEVEL_ERROR = 2;
    const LEVEL_DEBUG = 3;

    public function logInfo(string $message): void
    {
        $GLOBALS['temp_error_log'][] = [
            'message' => $message,
            'level' => ErrorLogger::LEVEL_INFO,
            'severity' => 'info',
            'created_at' => time(),
        ];
    }
    public function logWarning(string $message): void
    {
        $GLOBALS['temp_error_log'][] = [
            'message' => $message,
            'level' => ErrorLogger::LEVEL_WARNING,
            'severity' => 'warning',
            'created_at' => time(),
        ];
    }

    public function logError(string $message): void
    {
        $GLOBALS['temp_error_log'][] = [
            'message' => $message,
            'level' => ErrorLogger::LEVEL_ERROR,
            'severity' => 'error',
            'created_at' => time(),
        ];
    }
    public function logDebug(string $message): void
    {
        $GLOBALS['temp_error_log'][] = [
            'message' => $message,
            'level' => ErrorLogger::LEVEL_DEBUG,
            'severity' => 'debug',
            'created_at' => time(),
        ];
    }

    public static function logger(): ErrorLogger
    {
        return new self();
    }

    public function __destruct()
    {
        $system = new SystemDirectory();
        $log_file = $system->setting_dir . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'error.log';
        if (!file_exists($log_file)) {
            touch($log_file);
        }
        $content = array_map(function ($item) {
            return "\n---------------------\nLEVEL: {$item['level']}--\nSEVERITY: {$item['severity']}--\nMESSAGE: {$item['message']}--\nCREATED: {$item['created_at']}\n---------------------\n";
        }, $GLOBALS['temp_error_log']);
        file_put_contents($log_file, implode("\n", $content), FILE_APPEND);
        unset($system);
    }
}