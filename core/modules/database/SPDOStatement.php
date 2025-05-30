<?php

namespace Simp\Core\modules\database;

use PDOStatement;
use PDO;
use Throwable;
use RuntimeException; // For throwing errors

class SPDOStatement extends PDOStatement
{
    // Store bound parameters for cache key generation
    private array $boundParams = [];
    // Store parameters passed directly to execute()
    private ?array $executeParams = null;

    private ?DatabaseCacheManager $cacheManager = null;
    private float $lastExecutionTime = 0.0;
    private bool $isExecuted = false; // Track if execute() was called

    // Cache settings for this statement (can be overridden)
    private bool $enableCache = false;
    private ?string $cacheTag = null;
    private bool $cacheHit = false;
    private mixed $cachedResult = null;

    // Inject DatabaseCacheManager (dependency injection is better, but sticking to factory for now)
    protected function __construct()
    {
        // Protected constructor ensures PDO creates instances
        try {
            $this->cacheManager = DatabaseCacheManager::manager();
            // Check if caching is globally enabled via the manager's settings
            $this->enableCache = $this->cacheManager->isCacheActive();
        } catch (Throwable $e) {
            // Log error if cache manager fails to initialize
            Database::staticLogger("SPDOStatement Error: Failed to initialize DatabaseCacheManager: " . $e->getMessage());
            $this->cacheManager = null;
            $this->enableCache = false;
        }
    }

    /**
     * Overrides PDOStatement::execute to record execution time, parameters, and handle cache invalidation.
     *
     * @param array|null $params Input parameters for the prepared statement.
     * @return bool Returns true on success or false on failure.
     */
    public function execute(?array $params = null): bool
    {
        $this->executeParams = $params; // Store params passed to execute
        $this->isExecuted = false; // Reset execution status
        $this->cacheHit = false; // Reset cache status
        $this->cachedResult = null;
        $this->cacheTag = null;
        $isModifyingQuery = false; // Flag for INSERT/UPDATE/DELETE

        // Determine if it's a potentially modifying query BEFORE execution
        $trimmedQuery = ltrim($this->queryString);
        if (stripos($trimmedQuery, 'INSERT') === 0 ||
            stripos($trimmedQuery, 'UPDATE') === 0 ||
            stripos($trimmedQuery, 'DELETE') === 0) {
            $isModifyingQuery = true;
        }

        // --- Caching Logic (for SELECT queries) ---
        if (!$isModifyingQuery && $this->enableCache && $this->cacheManager) {
            try {
                // Combine bound params and execute params for cache key
                $allParams = array_merge($this->boundParams, $this->executeParams ?? []);
                $this->cacheTag = $this->cacheManager->cacheTagCreate($this->queryString, $allParams);

                // Check cache before executing (only useful for SELECT)
                if ($this->cacheManager->isTagCached($this->cacheTag)) {
                    // We will check the cache properly in fetch* methods
                    // No need to do anything here, just generated the tag
                }
            } catch (Throwable $e) {
                Database::staticLogger("SPDOStatement Cache Error (execute - check): " . $e->getMessage() . " for query: " . $this->queryString);
                // Disable cache for this statement if tag generation/check fails
                $this->enableCache = false;
            }
        }
        // --- End Caching Logic ---

        $start = microtime(true);
        try {
            $result = parent::execute($params);
            $this->isExecuted = $result;
        } catch (Throwable $e) {
            $this->isExecuted = false;
            // Log execution time even on failure
            $this->lastExecutionTime = microtime(true) - $start;
            Database::staticLogger("SPDOStatement Execute Error: " . $e->getMessage() . " Query: " . $this->queryString);
            throw $e; // Re-throw original exception
        }
        $this->lastExecutionTime = microtime(true) - $start;

        // Record query execution details
        try {
            DatabaseRecorder::factory($this->queryString, $this->lastExecutionTime, $this->executeParams ?? $this->boundParams);
        } catch (Throwable $e) {
            Database::staticLogger("SPDOStatement Error: Failed to record query: " . $e->getMessage());
        }

        // --- Cache Invalidation Logic ---
        if ($this->isExecuted && $isModifyingQuery && $this->enableCache && $this->cacheManager) {
            try {
                Database::staticLogger("SPDOStatement Cache Invalidation: Clearing cache due to successful modifying query: " . $this->queryString);
                $cleared = $this->cacheManager->clearAllCache();
                if (!$cleared) {
                    Database::staticLogger("SPDOStatement Cache Warning: clearAllCache() returned false.");
                }
            } catch (Throwable $e) {
                Database::staticLogger("SPDOStatement Cache Error (execute - invalidate): " . $e->getMessage() . " for query: " . $this->queryString);
                // Don't let cache clearing failure break the main execution flow
            }
        }
        // --- End Cache Invalidation Logic ---

        return $this->isExecuted;
    }

    public function getLastExecutionTime(): float
    {
        return $this->lastExecutionTime;
    }

    // Override bind methods to store parameters for cache key generation
    public function bindColumn(int|string $column, mixed &$var, int $type = PDO::PARAM_STR, int $maxLength = 0, mixed $driverOptions = null): bool
    {
        // bindColumn doesn't affect query parameters, so no need to store for cache key
        return parent::bindColumn($column, $var, $type, $maxLength, $driverOptions);
    }

    public function bindParam(int|string $param, mixed &$var, int $type = PDO::PARAM_STR, int $maxLength = 0, mixed $driverOptions = null): bool
    {
        // Storing reference is tricky for caching. Rely on execute() params or bindValue.
        return parent::bindParam($param, $var, $type, $maxLength, $driverOptions);
    }

    public function bindValue(int|string $param, mixed $value, int $type = PDO::PARAM_STR): bool
    {
        $this->boundParams[$param] = $value; // Store the actual value
        return parent::bindValue($param, $value, $type);
    }

    // --- Fetch Methods with Caching ---

    private function checkCache(): bool
    {
        // Only check cache if it's enabled, manager exists, and a tag was generated (likely a SELECT)
        if ($this->enableCache && $this->cacheManager && $this->cacheTag) {
            try {
                $cachedData = $this->cacheManager->getCache($this->cacheTag);
                // Check if data was actually retrieved (could be null in cache vs cache miss)
                if ($this->cacheManager->isTagCached($this->cacheTag) && $cachedData !== null) {
                    $this->cachedResult = $cachedData;
                    $this->cacheHit = true;
                    return true;
                }
            } catch (Throwable $e) {
                Database::staticLogger("SPDOStatement Cache Error (checkCache - get): " . $e->getMessage() . " for tag: " . $this->cacheTag);
                $this->enableCache = false; // Disable cache on error for this statement
            }
        }
        $this->cacheHit = false;
        return false;
    }

    private function saveToCache(mixed $data): void
    {
        // Only save if cache enabled, manager exists, tag generated, and it wasn't a cache hit
        if ($this->enableCache && $this->cacheManager && $this->cacheTag && !$this->cacheHit) {
            try {
                $this->cacheManager->resultCache($this->cacheTag, $data);
            } catch (Throwable $e) {
                Database::staticLogger("SPDOStatement Cache Error (saveToCache - set): " . $e->getMessage() . " for tag: " . $this->cacheTag);
                $this->enableCache = false; // Disable cache on error for this statement
            }
        }
    }

    public function fetchAll(int $mode = PDO::FETCH_DEFAULT, ...$args): array
    {
        if (!$this->isExecuted) {
            throw new RuntimeException("Cannot fetchAll before executing the statement.");
        }

        // Check cache first
        if ($this->checkCache()) {
            // Ensure cached result is an array for fetchAll
            return is_array($this->cachedResult) ? $this->cachedResult : [];
        }

        // Fetch from database if not cached
        $result = parent::fetchAll($mode, ...$args);
        // Save the result to cache
        $this->saveToCache($result);
        return $result;
    }

    // fetch, fetchColumn, fetchObject remain unchanged (no caching for simplicity/correctness)
    public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed
    {
        if (!$this->isExecuted) {
            throw new RuntimeException("Cannot fetch before executing the statement.");
        }
        return parent::fetch($mode, $cursorOrientation, $cursorOffset);
    }

    public function fetchColumn(int $column = 0): mixed
    {
        if (!$this->isExecuted) {
            throw new RuntimeException("Cannot fetchColumn before executing the statement.");
        }
        // Caching single column is complex, rely on fetchAll cache if needed
        return parent::fetchColumn($column);
    }

    public function fetchObject(?string $class = "stdClass", array $constructorArgs = []): object|false
    {
        if (!$this->isExecuted) {
            throw new RuntimeException("Cannot fetchObject before executing the statement.");
        }
        // Caching single object is complex, rely on fetchAll cache if needed
        return parent::fetchObject($class, $constructorArgs);
    }

    // Method to explicitly disable caching for this specific statement instance
    public function disableStatementCache(): void
    {
        $this->enableCache = false;
    }
}

