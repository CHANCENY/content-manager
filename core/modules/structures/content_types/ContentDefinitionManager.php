<?php

namespace Simp\Core\modules\structures\content_types;

use Simp\Core\lib\installation\SystemDirectory;
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
        $file .= DIRECTORY_SEPARATOR . "content-types.yml";
        if (!file_exists($file)) {
            touch($file);
        }
        $content_types = Yaml::parse(file_get_contents($file));
        if (empty($content_types)) {
            $this->content_types = [];
        }else {
            $this->content_types = $content_types;
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

    public function addContentType(string $name, array $config = []): void
    {
        $this->content_types[$name] = $config;
        file_put_contents($this->content_file, Yaml::dump($this->content_types,Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));
    }

    public function removeContentType(string $name): bool
    {
        if (isset($this->content_types[$name])) {
            unset($this->content_types[$name]);
        }
        return file_put_contents($this->content_file, Yaml::dump($this->content_types,Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));
    }

    public function addField(string $name, string $field_name, array $config = []): bool
    {
        $this->content_types[$name]['fields'][$field_name] = $config;
        if (file_put_contents($this->content_file, Yaml::dump($this->content_types,Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK))) {
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
        if (file_put_contents($this->content_file, Yaml::dump($this->content_types,Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK))) {
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