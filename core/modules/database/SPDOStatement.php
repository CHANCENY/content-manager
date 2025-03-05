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
        if (!empty($params)) {
            $this->params = array_merge($this->params, $params);
        }
        $start_time = microtime(true);
        $cache_tag = $this->cacheManager->cacheTagCreate($this->queryString, $this->params);
        $result = false;
        if (str_starts_with(strtolower($this->queryString), 'select')) {

            // Answer question of do we have cached data for this given query string.
            try{
                $this->skip_for_cache = $this->cacheManager->isTagCached($cache_tag);
            }catch (Throwable $exception){
            }
        }

        if ($this->skip_for_cache === false) {
            $result = parent::execute($params);
        }

        $end_time = microtime(true);
        $execution_time = $end_time - $start_time;
        $log = "Execution Time: {$execution_time} seconds: Query: {$this->queryString}".PHP_EOL;
        Database::logger($log);
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
        $cache_tag = $this->cacheManager->cacheTagCreate($this->queryString, $this->params);
        if ($this->skip_for_cache === true) {
           return $this->getFromCache($cache_tag, []);
        }else {
             $results = parent::fetchAll($mode);
             $this->saveNewCache($cache_tag, $results);
            return $results;
        }
    }

    public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed
    {
        $cache_tag = $this->cacheManager->cacheTagCreate($this->queryString, $this->params);
        try{
            if ($this->cacheManager->isTagCached($cache_tag)) {
                return $this->getFromCache($cache_tag, [])[$cursorOrientation] ?? null;
            }
        }catch (Throwable $exception){}

        $results = parent::fetch($mode, $cursorOrientation, $cursorOffset);
        $this->saveNewCache($cache_tag, $results);
        return $results;
    }

    public function fetchColumn(int $column = 0): mixed
    {
        $cache_tag = $this->cacheManager->cacheTagCreate($this->queryString, $this->params);
        try{
            if ($this->cacheManager->isTagCached($cache_tag)) {
                $data = $this->getFromCache($cache_tag, []);
                return array_map(function ($item) use ($column) {
                    if (is_object($item)) {
                        $item = (array) $item;
                        return array_values($item)[$column] ?? null;
                    }
                },$data);
            }
        }catch (Throwable $exception){}

        $result = parent::fetchColumn($column);
        $this->saveNewCache($cache_tag, $result);
        return $result;
    }

    public function fetchObject(?string $class = "stdClass", array $constructorArgs = []): object|false
    {
        $cache_tag = $this->cacheManager->cacheTagCreate($this->queryString, $this->params);
        if ($this->skip_for_cache === true) {
            $data = $this->getFromCache($cache_tag, []);
            if (is_object($data) && $class === 'stdClass') {
                return $data;
            }
            if (is_array($data) && array_key_exists(0, $data)) {
                $data = $data[0];
            }

            $data = (array) $data;
            if ($class === 'stdClass') {
                return (object) $data;
            }

            if (!empty($constructorArgs)) {
                $combine = [];
                foreach ($constructorArgs as $k=>$arg) {
                    $combine[$arg] = $data[$arg] ?? null;
                }
                return new $class(...$combine);
            }
            return new $class(...$data);
        }
        $result = parent::fetchObject($class, $constructorArgs);
        $this->saveNewCache($cache_tag, $result);
        return $result;
    }

}