<?php

namespace Simp\Core\modules\logger;

use Simp\Core\lib\installation\SystemDirectory;
use SplFileObject;

class ServerLogger extends SystemDirectory
{
    protected array $logs = [];

    public function __construct(int $limit = 50, int $offset = 0)
    {
        parent::__construct();
        $log_file = $this->setting_dir . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'app.log';

        if (file_exists($log_file)) {
            $file = new SplFileObject($log_file, 'r');
            if ($offset > 0) {
                $offset = ($offset + $limit) - 1;
            }
            $file->seek($offset);

            $lines_read = 0;
            while (!$file->eof() && $lines_read < $limit) {
                $line = trim($file->fgets());
                if (!empty($line)) {
                    $line_parts = explode(' ', $line);
                    if (count($line_parts) >= 4) {
                        $one = [];
                        foreach ($line_parts as $item) {
                            $line_one = explode(':', $item);
                            if (count($line_one) >= 2) {
                                $key = $line_one[0];
                                $value = trim(end($line_one));

                                if (in_array($key, ['elapsed', 'start', 'end'])) {
                                    $one[$key] = $this->date($value);
                                } elseif ($key === 'memory') {
                                    $one[$key] = $this->readableMemory($value);
                                } else {
                                    $one[$key] = $value;
                                }
                            }
                        }
                        $this->logs[] = $one;
                    }
                    $lines_read++;
                }
            }

            sort($this->logs);
        }
    }

    public function getFilterNumber(int $limit = 50): array
    {
        $log_file = $this->setting_dir . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'app.log';
        $filters = [
            'limit' => $limit,
            'offset_max' => 1,
        ];
        if (file_exists($log_file)) {
            $filesize = filesize($log_file);
            $filters['offset_max'] = floor($filesize / $limit);
        }
        return $filters;
    }

    protected function date($microtime): string
    {
        $timestamp = floor($microtime);
        $milliseconds = ($microtime - $timestamp) * 1000;
        return date("Y-m-d H:i:s", $timestamp) .' ' . sprintf("%03d", $milliseconds).'ms';
    }

    protected function readableMemory($size): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 2) . ' ' . $units[$i];
    }

    public function getLogs(): array
    {
        return $this->logs;
    }
}