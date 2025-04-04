<?php

use Simp\Core\lib\memory\cache\Caching;
use Simp\Core\modules\files\helpers\FileFunction;
use Simp\Core\modules\structures\content_types\ContentDefinitionManager;

function getContentType(?string $content_name): ?array
{
    if (empty($content_name)) {
        return null;
    }
    return ContentDefinitionManager::contentDefinitionManager()
        ->getContentType($content_name);
}

function getContentTypeField(?string $content_name, ?string $field_name): ?array
{
    if (empty($content_name) || empty($field_name)) {
        return null;
    }
    $content = getContentType($content_name);
    $fields = $content['fields'] ?? [];
    foreach ($fields as $field) {
        if (isset($field['inner_field'])) {
            $result = recursive_function($field['inner_field'], $field_name);
            if ($result) {
                return $result;
            }
        }
        else {
            if ($field['name'] == $field_name) {
                return $field;
            }
        }
    }
    return null;
}

function recursive_function(array $fields, string $field_name)
{
    foreach ($fields as $field) {
        if (isset($field['inner_field'])) {
            $result = recursive_function($field['inner_field'], $field_name);
            if ($result) {
                return $result;
            }
        }
        else {
            if ($field['name'] == $field_name) {
                return $field;
            }
        }
    }
}

function routeByName(?string $route_name)
{
    if (empty($route_name)) {
        return null;
    }
    try {
        return Caching::init()->get($route_name);
    }catch (Throwable $exception){
        return null;
    }
}

function breakLineToHtml(string $text,int $at = 100): string
{
    $text_line = null;
    $counter = 0;
    for($i = 0; $i < strlen($text); $i++) {
        if ($counter === $at) {
            $text_line .= "\n". $text[$i];
            $counter = 0;
        }
        else {
            $text_line .= $text[$i];
        }
        $counter++;
    }
    return nl2br($text_line);
}


return array(
    new \Twig\TwigFunction('get_content_type', function ($content_name) { return getContentType($content_name); }),
    new \Twig\TwigFunction('get_content_type_field', function ($content_name, $field_name) { return getContentTypeField($content_name, $field_name); }),
    new \Twig\TwigFunction('route_by_name', function ($route_name) { return routeByName($route_name); }),
    new \Twig\TwigFunction('file_uri', function ($fid) { return FileFunction::resolve_fid($fid); }),
    new \Twig\TwigFunction('file', function ($fid) { return FileFunction::file($fid); }),
    new \Twig\TwigFunction('br', function ($text) { return breakLineToHtml($text); }),
);