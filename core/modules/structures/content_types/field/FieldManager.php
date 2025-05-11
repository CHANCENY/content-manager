<?php

namespace Simp\Core\modules\structures\content_types\field;

use Simp\Core\lib\installation\SystemDirectory;
use Simp\Core\modules\structures\content_types\field\fields\InputFieldBuilder;

class FieldManager
{
    protected array $supported_fields;

    public function __construct()
    {
        $this->supported_fields = [
            'text' => InputFieldBuilder::class,
            'number' => InputFieldBuilder::class,
            'email' => InputFieldBuilder::class,
            'date' => InputFieldBuilder::class,
            'datetime' => InputFieldBuilder::class,
            'datetime-local' => InputFieldBuilder::class,
            'time' => InputFieldBuilder::class,
            'month' => InputFieldBuilder::class,
            'week' => InputFieldBuilder::class,
            'checkbox' => InputFieldBuilder::class,
            'radio' => InputFieldBuilder::class,
            'tel' => InputFieldBuilder::class,
            'url' => InputFieldBuilder::class,
            'search' => InputFieldBuilder::class,
            'range' => InputFieldBuilder::class,
            'color' => InputFieldBuilder::class,
            'password' => InputFieldBuilder::class,
            'hidden' => InputFieldBuilder::class,
            'submit' => InputFieldBuilder::class,
            'reset' => InputFieldBuilder::class,
            'button' => InputFieldBuilder::class,
        ];
        $system = new SystemDirectory();
        $extension_file = $system->setting_dir . DIRECTORY_SEPARATOR . 'fields' . DIRECTORY_SEPARATOR . 'fields.php';
        if (file_exists($extension_file)) {
            $fields = include $extension_file;
            if (!is_array($fields)) {
                $this->supported_fields = [
                  ...$this->supported_fields,
                  ...$fields,
                ];
            }
        }
    }

    public function getFieldBuilderHandler(string $type)
    {
        return $this->supported_fields[$type] ?? null;
    }

    public function getSupportedFieldsType(): array
    {
        return array_keys($this->supported_fields);
    }

    public function getFieldInfo(string $type): array
    {
        $handler = $this->getFieldBuilderHandler($type);
        if ($handler) {
            $handler = new $handler();
            if ($handler instanceof FieldBuilderInterface) {
                return $handler->extensionInfo($type);
            }
        }
        return [];
    }

    public static function fieldManager(): FieldManager {
        return new self();
    }
}
