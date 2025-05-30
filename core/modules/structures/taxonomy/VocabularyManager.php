<?php

namespace Simp\Core\modules\structures\taxonomy;

use Simp\Core\lib\installation\SystemDirectory;
use Symfony\Component\Yaml\Yaml;

class VocabularyManager
{
    protected array $vocabularies = [];
    protected string $location;

    public function __construct()
    {
        $system = new SystemDirectory;
        $this->location = $system->setting_dir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'taxonomy' . DIRECTORY_SEPARATOR . 'vocabulary.yml';
        if (!is_dir($system->setting_dir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'taxonomy')) {
            @mkdir($system->setting_dir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'taxonomy');
        }
        if (!file_exists($this->location)) {
            @touch($this->location);
        }
        $this->vocabularies = Yaml::parseFile($this->location) ?? [];
    }

    public function addVocabulary(string $name): true
    {
        if (!isset($this->vocabularies[$name])) {
            $this->vocabularies[$name] = [
                'name' => strtolower($this->createName($name)),
                'label' => $name
            ];
            $d = Yaml::dump($this->vocabularies, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
            return !file_put_contents($this->location, $d);
        }

        $this->vocabularies[$name]['label'] = $name;
        $d = Yaml::dump($this->vocabularies, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
        return !file_put_contents($this->location, $d);
    }

    protected function createName(string $name): string
    {
        // remove all special characters and replace with underscore
        // remove all spaces and replace with underscore
        // remove consecutive underscores and replace with single underscore
        $name = preg_replace('/[^a-zA-Z0-9_]/', '', $name);
        $name = preg_replace('/\s+/', '_', $name);
        return preg_replace('/_+/', '_', $name);
    }

    public static function factory(): VocabularyManager
    {
        return new self();
    }

    public function removeVocabulary(string $vid): false
    {
        if (isset($this->vocabularies[$vid])) {
            unset($this->vocabularies[$vid]);
            $d = Yaml::dump($this->vocabularies, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
            return !file_put_contents($this->location, $d);
        }
        return false;
    }
}