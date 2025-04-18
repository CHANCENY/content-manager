<?php

namespace Simp\Core\modules\structures\content_types;

use Simp\Core\lib\installation\SystemDirectory;
use Simp\Core\modules\database\Database;
use Simp\Core\modules\structures\content_types\storage\ContentDefinitionStorage;
use Symfony\Component\Yaml\Yaml;

class ContentDefinitionManager extends SystemDirectory
{
    protected array $content_types = [];
    protected string $content_file;

    public function __construct()
    {
        parent::__construct();
        $file = $this->setting_dir . DIRECTORY_SEPARATOR . "config";
        @mkdir($file);
        $file .= DIRECTORY_SEPARATOR . "content_types";
        @mkdir($file);
        $list = array_diff(scandir($file)?? [], ['.', '..']);
        foreach($list as $file_name) {
            $full_name = $file . DIRECTORY_SEPARATOR . $file_name;
            if (file_exists($full_name)) {
                $content = Yaml::parseFile($full_name);
                $list_n = pathinfo($full_name, PATHINFO_FILENAME);
                $this->content_types[$list_n] = $content;
            }
        }
        $this->content_file = $file;
    }

    public function getContentTypes(): array {
        return $this->content_types;
    }

    public function getContentType(string $name): ?array
    {
        return $this->content_types[$name] ?? null;
    }

    private function savable($name, array $all) {
        return $all[$name] ?? [];
    }

    public function addContentType(string $name, array $config = []): void
    {
        $this->content_types[$name] = $config;
        file_put_contents($this->content_file .DIRECTORY_SEPARATOR.$name.'.yml'  , Yaml::dump(
            $this->savable($name, $this->content_types),Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));
    }

    public function removeContentType(string $name): bool
    {
        if (isset($this->content_types[$name])) {
            unset($this->content_types[$name]);
            $storage = ContentDefinitionStorage::contentDefinitionStorage($name);
            $storages = $this->content_types['storage'] ?? [];
            foreach($storages as $store) {
                $name_t = substr($store, 5, strlen($store));
                $name_t = trim($name_t, '_');
                $storage->removeStorageDefinition($name_t);
            }
            $query = "DELETE FROM node_data WHERE bundle = :name";
            $query = Database::database()->con()->prepare($query);
            $query->bindParam(':name', $name);
            $query->execute();
        }
        return @unlink($this->content_file. DIRECTORY_SEPARATOR. $name . '.yml');
    }

    public function addField(string $name, string $field_name, array $config = [], bool $persist = false, array $persist_override = []): bool
    {
        $this->content_types[$name]['fields'][$field_name] = $config;
        if (file_put_contents($this->content_file . DIRECTORY_SEPARATOR . $name . '.yml', 
        Yaml::dump($this->savable($name, $this->content_types),Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK))) {

            $storage = ContentDefinitionStorage::contentDefinitionStorage($name);
            // process the tables.
            if ($persist) {
                $storage->storageDefinitionsPersistent($persist_override);
            }
            return true;
        }
        return false;
    }

    public function getField(string $name, string $field_name): ?array
    {
        return $this->content_types[$name]['fields'][$field_name] ?? null;
    }

    public function removeField(string $name, string $field_name): bool
    {
        if (isset($this->content_types[$name]['fields'][$field_name])) {
            unset($this->content_types[$name]['fields'][$field_name]);
        }

        $index = array_search('node__' . $field_name, $this->content_types[$name]['storage']);
        if ($index !== false) {
            unset($this->content_types[$name]['storage'][$index]);
            $delete_query = ContentDefinitionStorage::contentDefinitionStorage($name)->getStorageDropStatement($field_name);
            $sta = Database::database()->con()->prepare($delete_query);
            $sta->execute();
        }
        if (file_put_contents($this->content_file . DIRECTORY_SEPARATOR . $name . '.yml',
         Yaml::dump($this->savable($name, $this->content_types),Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK))) {
            return true;
        }
        return false;
    }

    public function removeInnerField(string $name,string $parent_field, string $field_name): bool
    {
        if (isset($this->content_types[$name]['fields'][$parent_field]['inner_field'][$field_name])) {
            unset($this->content_types[$name]['fields'][$parent_field]['inner_field'][$field_name]);
        }

        $index = array_search('node__' . $field_name, $this->content_types[$name]['storage']);
        if ($index !== false) {
            unset($this->content_types[$name]['storage'][$index]);
            $delete_query = ContentDefinitionStorage::contentDefinitionStorage($name)->getStorageDropStatement($field_name);
            $sta = Database::database()->con()->prepare($delete_query);
            $sta->execute();
        }
        if (file_put_contents($this->content_file . DIRECTORY_SEPARATOR . $name . '.yml', 
        Yaml::dump($this->savable($name, $this->content_types),Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK))) {
            return true;
        }
        return false;
    }

    public function getContentTypeStorage(): string
    {
        return $this->content_file;
    }

    public static function contentDefinitionManager(): self
    {
        return new self();
    }
}