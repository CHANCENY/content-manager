<?php

namespace Simp\Core\modules\database;

use PDO;
use Simp\Core\lib\installation\InstallerValidator;
use Simp\Core\lib\installation\SystemDirectory;
use Symfony\Component\Yaml\Yaml;

class Database
{
    protected PDO $pdo;

    public function __construct(
        protected string $hostname,
        protected string $dbname,
        protected string $username,
        protected string $password,
        protected int $port,
        protected string $dsn,
        protected array $cache,
        protected bool $log,
    )
    {
        $this->dsn = str_replace(
            ['$host', '$port', '$user', '$password', '$dbname'],
            [$this->hostname, $this->port, $this->username, $this->password, $this->dbname],
            $this->dsn
        );

        try {
            date_default_timezone_set('UTC');
            $this->pdo = new SPDO($this->dsn, $this->username, $this->password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $this->pdo->setAttribute(PDO::ATTR_PERSISTENT, true);
            $this->pdo->setAttribute(PDO::ATTR_STATEMENT_CLASS, [SPDOStatement::class, []]);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
            if ($this->log === true) {
                $content = "CONNECTION [OK] ".$this->pdo->getAttribute(PDO::ATTR_SERVER_INFO).PHP_EOL;
                $this->pdo->log($content);
            }
        }catch (\Throwable $exception){
            if ($this->log === true) {
                exit("Error: database connection failed: ".$exception->getMessage());
            }
        }
    }

    public function con(): SPDO
    {
        return $this->pdo;
    }

    public static function database(): ?Database
    {
        $system = new SystemDirectory();
        $database_setting = $system->setting_dir . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'database.yml';
        if (file_exists($database_setting)) {
            $settings = Yaml::parse(file_get_contents($database_setting));
            return new Database(...$settings);
        }
        return null;
    }

    public static function logger(string $log): void
    {
        $system = $GLOBALS['system_store'] ?? null;
        if($system instanceof InstallerValidator) {
            $settings = $system->setting_dir . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'database.yml';
            if (file_exists($settings)) {
                $settings = Yaml::parse(file_get_contents($settings));
                if (!empty($settings['log']) && $settings['log'] === true) {
                    $log_file = $system->setting_dir . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'database.log';
                    file_put_contents($log_file, $log.PHP_EOL, FILE_APPEND | LOCK_EX);
                }
            }
        }
    }
}