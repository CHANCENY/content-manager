<?php

namespace Simp\Core\modules\search;

use Simp\Core\lib\installation\SystemDirectory;
use Simp\Core\lib\memory\cache\Caching;
use Simp\Core\modules\database\Database;
use Simp\Core\modules\structures\content_types\ContentDefinitionManager;
use Symfony\Component\Yaml\Yaml;

class SearchManager
{

    protected array $settings = [];
    protected string $location = '';
    public function __construct()
    {
        $system = new SystemDirectory();
        $search_config = $system->setting_dir . DIRECTORY_SEPARATOR . 'config';
        if (!is_dir($search_config)) {
            mkdir($search_config);
        }
        $search_config .=  DIRECTORY_SEPARATOR . 'search';
        if (!is_dir($search_config)) {
            mkdir($search_config);
        }
        $search_config .= DIRECTORY_SEPARATOR . 'search.yml';
        if (!file_exists($search_config)) {
            @touch($search_config);
        }

        $defaults = Caching::init()->get('default.admin.search.setting');
        if (!empty($defaults) && file_exists($defaults)) {
            $defaults = Yaml::parseFile($defaults) ?? [];
        }

        $custom = Yaml::parseFile($search_config) ?? [];
        $this->settings = array_merge($defaults, $custom);
        $this->location = $search_config;
    }

    public function addSetting(string $key, array $value): bool
    {
        $this->settings[$key] = $value;
        return !empty(file_put_contents($this->location, Yaml::dump($this->settings,Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK)));

    }

    public function getSettings(): array
    {
        return $this->settings;
    }
    public function getLocation(): string {
        return $this->location;
    }

    public function getSetting(string $key) {
        return $this->settings[$key] ?? null;
    }

    public function removeSetting(string $key): bool
    {
        if (isset($this->settings[$key])) {
            unset($this->settings[$key]);
            return !empty(file_put_contents($this->location, Yaml::dump($this->settings,Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK)));
        }
        return false;
    }

    public function getSourceSearchableField(string $key): array
    {
        $setting  = $this->getSetting($key);

        if ($setting) {
            if (isset($setting['type']) && $setting['type'] == 'content_type') {
                $fields = [];
                if (isset($setting['sources'])) {
                    foreach ($setting['sources'] as $source) {
                        $fields = array_merge($fields, $this->getContentTypeFields($source));
                    }
                }
                $fields = array_combine(array_values($fields), array_values($fields));
                return [
                    'node_data:title' => 'Title',
                    'node_data:author' => 'Author',
                    'node_data:created' => 'Authored on',
                    'node_data:updated' => 'Updated on',
                    'node_data:nid' => 'Node ID',
                    'node_data:lang' => 'Language',
                    ...$fields
                ];
            }
            elseif (isset($setting['type']) && $setting['type'] == 'user_type') {
                return [
                    'users:name' => 'Username',
                    'users:created' => 'Created on',
                    'users:updated' => 'Updated on',
                    'user_profile:first_name' => 'First Name',
                    'user_profile:last_name' => 'Last Name',
                    'user_profile:description' => 'Profile Description',
                    'user_profile:time_zone' => 'Time Zone',
                    'user_profile:profile_image' => 'Profile Image',
                ];
            }
            elseif (isset($setting['type']) && $setting['type'] == 'database_type') {
                $query = Database::database()->con()->prepare("SHOW TABLES");
                $query->execute();
                $rows = $query->fetchAll(\PDO::FETCH_COLUMN);
                $new_rows = array_filter($rows, function ($row) {
                    $excludes = ['users', 'user_profile', 'user_roles', 'file_managed', 'node_data'];
                    return !in_array($row, $excludes);
                });
                return array_combine(array_values($new_rows), array_values($new_rows));
            }
        }
        return [];
    }

    public function getDatabaseSearchableColumns(string $source): array
    {
        $query = Database::database()->con()->prepare("SHOW COLUMNS FROM $source");
        $query->execute();
        $rows = $query->fetchAll(\PDO::FETCH_COLUMN);
        $new_rows = array_filter($rows, function ($row) use ($source) {
            return "$source:$row";
        },$rows);
        return array_combine(array_values($new_rows), array_values($new_rows));
    }

    protected function getContentTypeFields(string $source): array
    {
        $fields = ContentDefinitionManager::contentDefinitionManager()->getContentType($source);
        $storages = $fields['storage'] ?? [];
        return array_map(function ($field) use ($source) {
            return $source . ":". substr($field,6, strlen($field));
        },$storages);
    }

    public function buildSearchQuery(string $key): ?string
    {
        $definition = $this->getSetting($key);
        if ($definition) {
            dump($definition);
        }
        return null;
    }

    public static function searchManager(): self
    {
        return new self();
    }
}