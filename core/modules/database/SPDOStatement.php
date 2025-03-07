<?php

namespace Simp\Core\modules\database;

use PDO;
use Throwable;

class SPDOStatement extends \PDOStatement
{
    private array $params = [];
    private DatabaseCacheManager $cacheManager;

    private bool $skip_for_cache;
    protected function __construct() {
        // Protected constructor ensures PDO creates instances
        $this->cacheManager = DatabaseCacheManager::manager();
        $this->skip_for_cache = false;
    }
    public function execute(?array $params = null): bool
    {
        $result = parent::execute($params);
        return $result;
    }

    public function bindColumn(int|string $column, mixed &$var, int $type = PDO::PARAM_STR, int $maxLength = 0, mixed $driverOptions = null): bool
    {
        $this->params[$column] = $var;
        return parent::bindColumn($column, $var, $type, $maxLength, $driverOptions);
    }

    public function bindParam(int|string $param, mixed &$var, int $type = PDO::PARAM_STR, int $maxLength = 0, mixed $driverOptions = null): bool
    {
        $this->params[$param] = $var;
        return parent::bindParam($param, $var, $type, $maxLength, $driverOptions);
    }

    public function bindValue(int|string $param, mixed $value, int $type = PDO::PARAM_STR): bool
    {
        $this->params[$param] = $value;
        return parent::bindValue($param, $value, $type);
    }

    private function getFromCache(string $cache_tag, $default = null)
    {
        try {
            return $this->cacheManager->getCache($cache_tag);
        }catch (Throwable $exception){
            $log = "Cache issue: encountered for cache tag {$cache_tag} on {$this->queryString}".PHP_EOL;
            Database::logger($log);
        }
        return $default;
    }

    private function saveNewCache(string $cache_tag, $value): void
    {
        try{
            $this->cacheManager->resultCache($cache_tag, $value);
        }catch (Throwable $exception){
            $log = "CACHE ISSUE: failed to save query results for cache tag {$cache_tag} on {$this->queryString}".PHP_EOL;
            Database::logger($log);
        }
    }

    public function fetchAll(int $mode = PDO::FETCH_DEFAULT, ...$args): array
    {
        return parent::fetchAll($mode);
    }

    public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed
    {
        return parent::fetch($mode, $cursorOrientation, $cursorOffset);
    }

    public function fetchColumn(int $column = 0): mixed
    {

        return parent::fetchColumn($column);
    }

    public function fetchObject(?string $class = "stdClass", array $constructorArgs = []): object|false
    {
        return parent::fetchObject($class, $constructorArgs);

    }

}