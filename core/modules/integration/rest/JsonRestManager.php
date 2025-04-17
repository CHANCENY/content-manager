<?php

namespace Simp\Core\modules\integration\rest;

use Simp\Core\lib\installation\SystemDirectory;
use Simp\Core\lib\routes\Route;
use Simp\Core\modules\database\Database;
use Symfony\Component\Yaml\Yaml;

class JsonRestManager
{
    protected array $rest_versions = [];
    protected string $version_storage = '';
    protected array $version_routes = [];
    protected string $version_routes_storage = '';
    public function __construct() {
        $system_directory = new SystemDirectory();
        $this->version_storage = $system_directory->setting_dir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'rest' . DIRECTORY_SEPARATOR . 'json';
        if (!is_dir($this->version_storage)) {
            @mkdir($this->version_storage, recursive: true);

        }
        $this->version_storage .= DIRECTORY_SEPARATOR . 'versions.yml';
        if (!file_exists($this->version_storage)) {
            @touch($this->version_storage);
        }

        $this->version_routes_storage = $system_directory->setting_dir . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . 'general';
        if (!is_dir($this->version_routes_storage)) {
            @mkdir($this->version_routes_storage, recursive: true);
        }
        $this->version_routes_storage .= DIRECTORY_SEPARATOR . 'general-routes.yml';
        if (!file_exists($this->version_routes_storage)) {
            @touch($this->version_routes_storage);
        }

        $this->version_routes = Yaml::parseFile($this->version_routes_storage) ?? [];
        $this->rest_versions = Yaml::parseFile($this->version_storage) ?? [];
    }
    public function addVersion(string $title, string $version_key): bool {
        $version_key = strtolower($version_key);
        $this->rest_versions[$version_key] = [
            'title' => $title,
            'version_key' => $version_key,
            'status' => 1
        ];
        return !empty(file_put_contents($this->version_storage, Yaml::dump($this->rest_versions)));
    }
    public function getVersion(string $version_key): ?array
    {
        if (isset($this->rest_versions[$version_key])) {
            return $this->rest_versions[$version_key];
        }
        return null;
    }
    public function getVersions(): array
    {
        return $this->rest_versions;
    }
    public function updateVersion(string $version_key, array $data): bool
    {
        if (isset($this->rest_versions[$version_key])) {
            $this->rest_versions[$version_key] = $data;
            return !empty(file_put_contents($this->version_storage, Yaml::dump($this->rest_versions)));
        }
        return false;
    }

    public function deleteVersion(string $version_key): bool
    {
        if (isset($this->rest_versions[$version_key])) {
            unset($this->rest_versions[$version_key]);
            return !empty(file_put_contents($this->version_storage, Yaml::dump($this->rest_versions)));
        }
        return false;
    }

    public function addVersionRoute(string $key, array $data): bool
    {
        $this->version_routes[$key] = $data;
        return !empty(file_put_contents($this->version_routes_storage, Yaml::dump($this->version_routes,Yaml::DUMP_OBJECT_AS_MAP)));;
    }

    public function getVersionRoute(string $version_key): ?array {

        $found = array_filter($this->version_routes, function ($item) use ($version_key) {
            return isset($item['route_type']) && $item['route_type'] === "rest_". $version_key;
        });
        foreach ($found as $key => $route) {
            $found[$key] = new Route($key, $route);
        }
        return $found;
    }

    public function removeVersionRoute(string $route_key): bool
    {
        if (isset($this->version_routes[$route_key])) {
            unset($this->version_routes[$route_key]);
            return !empty(file_put_contents($this->version_routes_storage, Yaml::dump($this->version_routes,Yaml::DUMP_OBJECT_AS_MAP)));
        }
        return false;
    }

    public function addVersionRoutePostSetting(string $route_key, array $data): bool
    {
        $system_directory = new SystemDirectory();
        @mkdir($this->version_storage = $system_directory->setting_dir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'rest' . DIRECTORY_SEPARATOR . 'json',recursive: true);

        $this->version_storage .= DIRECTORY_SEPARATOR . 'post-settings.yml';
        if (!file_exists($this->version_storage)) {
            @touch($this->version_storage);
        }
        $post_settings = Yaml::parseFile($this->version_storage) ?? [];
        $table = str_replace('.', '_', $route_key);
        $data['data_source'] = $table;
        if (!$this->createTable($route_key, $data)){
            return false;
        }
        $post_settings[$route_key] = $data;
        return !empty(file_put_contents($this->version_storage, Yaml::dump($post_settings, Yaml::DUMP_OBJECT_AS_MAP)));
    }

    public function getVersionRoutePostSetting(string $route_key): array
    {
        $system_directory = new SystemDirectory();
        $this->version_storage = $system_directory->setting_dir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'rest' . DIRECTORY_SEPARATOR . 'json';
        if (!is_dir($this->version_storage)) {
            @mkdir($this->version_storage, recursive: true);
        }
        $this->version_storage .= DIRECTORY_SEPARATOR . 'post-settings.yml';
        if (!file_exists($this->version_storage)) {
            @touch($this->version_storage);
        }
        $post_settings = Yaml::parseFile($this->version_storage) ?? [];
        return $post_settings[$route_key] ?? [];
    }

    public function createTable(string $table, array $post_settings): bool {

        $query = [];
        foreach ($post_settings as $key=>$post_setting) {
            if (isset($post_setting['post_keys_type']) && $post_setting['post_keys_type'] === 'float') {
                $query[] = "`{$key}` DOUBLE ".(!isset($post_setting['post_keys_required']) && $post_setting['post_keys_required'] === 'yes' ? 'NOT NULL' : 'NULL');
            }
            elseif (isset($post_setting['post_keys_type']) && $post_setting['post_keys_type'] === 'string') {
                $query[] = "`{$key}` TEXT ".(!isset($post_setting['post_keys_required']) && $post_setting['post_keys_required'] === 'yes' ? 'NOT NULL' : 'NULL');
            }
            elseif (isset($post_setting['post_keys_type']) && $post_setting['post_keys_type'] === 'int') {
                $query[] = "`{$key}` INT ".(!isset($post_setting['post_keys_required']) && $post_setting['post_keys_required'] === 'yes' ? 'NOT NULL' : 'NULL');
            }

            elseif (isset($post_setting['post_keys_type']) && $post_setting['post_keys_type'] === 'array') {
                $query[] = "`{$key}` LONGBLOB ".(!isset($post_setting['post_keys_required']) && $post_setting['post_keys_required'] === 'yes' ? 'NOT NULL' : 'NULL');
            }
        }
        $line = implode(',',$query);
        $query = "CREATE TABLE IF NOT EXISTS `{$table}` ( {$line} )";
        try{
            $query = Database::database()->con()->prepare($query);
            return $query->execute();
        }catch (\Throwable $exception){
            return false;
        }
    }

    public static function factory(): JsonRestManager {
        return new JsonRestManager();
    }
}