<?php

namespace Simp\Core\modules\database;

use Phpfastcache\Exceptions\PhpfastcacheCoreException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Simp\Core\lib\installation\SystemDirectory;
use Simp\Core\lib\memory\cache\Caching;
use stdClass;
use Symfony\Component\Yaml\Yaml;

class DatabaseCacheManager extends SystemDirectory
{
    private object $database_settings;
    private Caching $caching;
    public function __construct()
    {
        parent::__construct();
        $this->database_settings = new StdClass();
        $setting_file = $this->setting_dir . DIRECTORY_SEPARATOR . 'database' .DIRECTORY_SEPARATOR .'database.yml';
        if (file_exists($setting_file)) {
            $this->database_settings = Yaml::parse(file_get_contents($setting_file), Yaml::PARSE_OBJECT_FOR_MAP);
        }
        $this->caching = Caching::init();
    }

    public function cacheTagCreate(string $query_string, array $params = []): string
    {
        $binding_keys = array_keys($params);
        if (!empty($binding_keys)) {
            foreach ($binding_keys as $key) {
                $sanitized_value = $params[$key];
                if (is_bool($params[$key])) {
                    $sanitized_value = $params[$key] ? 'true' : 'false';
                }
                $query_string = str_replace($key, $sanitized_value, $query_string);
            }
        }

        // TODO: append current user to cache tag if exist.
        return strtolower(self::clear($query_string));
    }

    private function clear($string): array|string
    {
        // Remove all special characters and replace with a space
        $string = preg_replace('/[^a-zA-Z0-9]/', ' ', $string);

        // Remove multiple spaces and replace spaces with dots
        return str_replace(' ', '.', trim(preg_replace('/\s+/', ' ', $string)));
    }

    /**
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function resultCache(string $cache_tag, $results): bool
    {
        if ($this->database_settings?->cache?->active) {
            return $this->caching->set($cache_tag, $results);
        }
        return false;
    }

    /**
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function getCache(string $cache_tag)
    {
        if ($this->database_settings?->cache?->active) {
            return $this->caching->get($cache_tag);
        }
        return null;
    }

    /**
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function isTagCached(string $cache_tag): bool
    {
        if ($this->database_settings?->cache?->active) {
            return $this->caching->has($cache_tag);
        }
        return false;
    }

    public static function manager(): DatabaseCacheManager
    {
        return new DatabaseCacheManager();
    }
}