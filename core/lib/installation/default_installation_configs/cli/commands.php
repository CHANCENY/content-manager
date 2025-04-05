<?php

$vendor = getcwd() . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (!file_exists($vendor)) {
    die('vendor directory not found. please run commands from root directory');
}

require_once $vendor;

use Phpfastcache\Exceptions\PhpfastcacheCoreException;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheDriverNotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheExtensionNotInstalledException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidConfigurationException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidTypeException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Simp\Core\lib\installation\InstallerValidator;
use Simp\Core\lib\installation\SystemDirectory;
use Symfony\Component\Yaml\Yaml;

global $system;
global $terminal_colors;
$terminal_colors = [
    'black' => '0;30',
    'red' => '0;31',
    'green' => '0;32',
    'yellow' => '0;33',
    'blue' => '0;34',
    'magenta' => '0;35',
    'cyan' => '0;36',
    'white' => '0;37',
    'bold_black' => '1;30',
    'bold_red' => '1;31',
    'bold_green' => '1;32',
    'bold_yellow' => '1;33',
    'bold_blue' => '1;34',
    'bold_magenta' => '1;35',
    'bold_cyan' => '1;36',
    'bold_white' => '1;37',
];

$system = new SystemDirectory();

function cache_clear(array $options): void
{
    global $system;
    global $terminal_colors;

    if (count($options) !== 1) {

        echo "\033[" . $terminal_colors['red'] . "m exact one option is required" . PHP_EOL;
        echo PHP_EOL;
        echo "--option first expected values eg 
        --clear,
        --cache,
        --session,
        --twig
        --services
        --routing
        --forms
        --logs
        --db-logs
        " .PHP_EOL.PHP_EOL;
        echo "\033[0m";
        return;
    }

    $option = $options[0];
    $cleanable_paths = [
        '--cache' => $system->var_dir . DIRECTORY_SEPARATOR . 'cache',
        '--session' => $system->var_dir . DIRECTORY_SEPARATOR . 'sessions',
        '--twig' => $system->var_dir . DIRECTORY_SEPARATOR . 'twig',
        '--services' => $system->var_dir . DIRECTORY_SEPARATOR . 'services',
        '--routing' => $system->var_dir . DIRECTORY_SEPARATOR . 'routing',
        '--forms' => $system->var_dir . DIRECTORY_SEPARATOR . 'form-build',
        '--logs' => $system->setting_dir . DIRECTORY_SEPARATOR . 'logs',
        '--db-logs' => $system->setting_dir . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'database.log',
    ];

    $location = $cleanable_paths[$option] ?? null;

    if (!empty($location)) {

        if (is_file($location) && file_exists($location)) {
            unlink($location);
            echo "\033[" . $terminal_colors['green'] . "m clearing done successfully\033[0m\n";
            return;
        }

        $list = array_diff(scandir($location)?? [], ['.', '..']);

        $recursive = function($dir) use (&$recursive) {
            $list_inner = array_diff(scandir($dir) ?? [], ['.', '..']);
            foreach ($list_inner as $item) {
                $full_inner_path = $dir . DIRECTORY_SEPARATOR . $item;
                if (is_file($full_inner_path) && file_exists($full_inner_path)) {
                    unlink($full_inner_path);
                }
                elseif (is_dir($full_inner_path)) {
                    $recursive($full_inner_path);
                    @rmdir($full_inner_path);
                }
            }
        };

        foreach ($list as $file) {
            $full_path = $location  . DIRECTORY_SEPARATOR . $file;
            if (is_file($full_path) && file_exists($full_path)) {
                unlink($full_path);
            }
            elseif (is_dir($full_path)) {
                $recursive($full_path);
                @rmdir($full_path);
            }
        }

        echo "\033[" . $terminal_colors['green'] . "m clearing done successfully\033[0m\n";
        return;
    }

    echo "\033[" . $terminal_colors['yellow'] . "m option not supported.\033[0m\n";
}

function user(array $options): void
{
    global $system;
    global $terminal_colors;
    $option = $options[0] ?? null;

    $supported = [
        '--find' => 'search_user',
        '--create' => 'create_user',
        '--update' => 'update_user',
        '--delete' => 'delete_user',
        '--block' => 'block_user',
    ];

    $found = $supported[$option] ?? null;
    if (empty($found)) {

        echo "\033[0m";
        echo "\033[" . $terminal_colors['red'] . "m exact one option is required" . PHP_EOL;
        echo PHP_EOL;
        echo "--option first expected values eg
        --find,
        --create,
        --update,
        --delete,
        --block,
        " .PHP_EOL.PHP_EOL;
        echo "\033[0m";
        return;
    }

     function search_user (): void {
        global $system;
        global $terminal_colors;

        while (true) {
            echo "You can search using email, uid, name".PHP_EOL;
            $input = readline("\nSearch User: ");
        }

        return;
    };

    $found();

}

return [
    'cleaner' => "cache_clear",
    'user' => "user"
];