<?php

namespace Simp\Core\modules\structures\content_types\helper;
use Simp\Core\modules\files\entity\File;
use Simp\Core\modules\files\helpers\FileFunction;

trait NodeFunction
{
    public static function findField(array $fields, string $field_name)
    {
        foreach ($fields as $key=>$field) {
            if ($field_name == $key) {
                return $field;
            }
            elseif (isset($field['inner_field'])) {
                $found = self::findField($field['inner_field'], $field_name);
                if ($found) {
                    return $found;
                }
            }
        }
        return [];
    }

    public function nodeFile(array|int $fid, bool $webroot = true): array
    {
        if (is_array($fid)) {
            $uri = [];
            foreach ($fid as $f) {
                $uri[] = FileFunction::reserve_uri(FileFunction::resolve_fid($f),$webroot);
            }
            return $uri;
        }
        return [FileFunction::reserve_uri(FileFunction::resolve_fid($fid),$webroot)];
    }

    public function nodeFileContent(array|int $fid, bool $webroot = true): array
    {
        if (empty($fid)) {
            return [];
        }
        $content = [];
        if (is_numeric($fid)) {
            $fid = [$fid];
        }

        foreach ($fid as $f) {
            if (!empty($f)) {
                $file = File::load($f);
                $content[] = [
                    'name' => $file->getName(),
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType(),
                    'uri' => FileFunction::reserve_uri(FileFunction::resolve_fid($f),$webroot),
                    'is_image' => str_starts_with($file->getMimeType(), 'image'),
                ];
            }
        }
        return $content;
    }
}