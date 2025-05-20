<?php

use Phpfastcache\Exceptions\PhpfastcacheCoreException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Simp\Core\lib\memory\cache\Caching;
use Simp\Core\modules\config\config\ConfigReadOnly;
use Simp\Core\modules\config\ConfigManager;
use Simp\Core\modules\files\helpers\FileFunction;
use Simp\Core\modules\search\SearchManager;
use Simp\Core\modules\structures\content_types\ContentDefinitionManager;
use Simp\Core\modules\structures\content_types\field\FieldBuilderInterface;
use Simp\Core\modules\structures\content_types\field\FieldManager;
use Simp\Core\modules\user\current_user\CurrentUser;
use Simp\Core\modules\user\entity\User;
use Simp\Translate\translate\Translate;
use Symfony\Component\HttpFoundation\Request;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\TwigFunction;

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

function url(string $id, array $options, array $params = []): string
{
    if (!empty($id)) {
        $route = Caching::init()->get($id);
        if ($route instanceof \Simp\Core\lib\routes\Route) {
            $pattern = $route->getRoutePath();
            function generatePath(string $pattern, array $values): string {
                $segments = explode('/', $pattern);

                foreach ($segments as &$segment) {
                    if (str_starts_with($segment, '[') && str_ends_with($segment, ']')) {
                        // Trim the square brackets
                        $placeholder = trim($segment, '[]');

                        // Handle possible type e.g., id:int
                        $parts = explode(':', $placeholder);
                        $key = $parts[0];

                        if (isset($values[$key])) {
                            $segment = $values[$key];
                        }
                    }
                }
                return implode('/', $segments);
            }
            $with_value_pattern = generatePath($pattern, $options);

            return empty($params) ? $with_value_pattern : $with_value_pattern . '?'. http_build_query($params);
        }
    }
    return '';
}

function author(int $uid): ?User {
    return User::load($uid);
}

/**
 * @throws PhpfastcacheCoreException
 * @throws PhpfastcacheLogicException
 * @throws PhpfastcacheDriverException
 * @throws PhpfastcacheInvalidArgumentException
 */
function t(string $text, ?string $from = null, ?string $to = null): string {

     // Check if current user has timezone translation enabled.
     $current_user = CurrentUser::currentUser();

    if ($current_user instanceof \Simp\Core\modules\auth\normal_auth\AuthUser) {
        if (!$current_user->getUser()->getProfile()->isTranslationEnabled()) {
            return $text;
        }
    }

    if (empty($to)) {
        if ($current_user?->getUser()?->getProfile()?->isTranslationEnabled()) {
            $to = $current_user?->getUser()?->getProfile()?->getTranslation();
        }else {
            $to = 'en';
        }
    }

    // Get the system language.
    if (empty($from)) {
        $config = ConfigManager::config()->getConfigFile('system.translation.settings');
        if ($config instanceof ConfigReadOnly) {
            $from = $config->get('system_lang', 'en');
        }
        else {
            $from = 'en';
        }
    }

    if (is_dir('public://translations')) {
        @mkdir('public://translations');
    }

    return Translate::translate($text,$from, $to, 'public://translations');
}

function translation(?string $code): ?array
{
    if (empty($code)) {
        return [];
    }
    return \Simp\Translate\lang\LanguageManager::manager()->getByCode($code);
}

/**
 * @throws RuntimeError
 * @throws SyntaxError
 * @throws LoaderError
 */
function search_api(string $search_key): ?string
{
    return SearchManager::buildForm($search_key);
}

function getFieldTypeInfo(string $type = ''): ?array
{
    return FieldManager::fieldManager()->getFieldInfo($type);
}

function get_field_type_info(string $type = '', $index = 0, array $field = []): string
{

    $options = $field;
    $options['index'] = $index;

    if ($type === 'textarea') {
        if (in_array('editor', $field['class'] ?? [])) {
            $type = 'ck_editor';
        }
        else {
            $type = 'simple_textarea';
        }
    }
    if ($type === 'conditional') {
        $type = 'fieldset';
    }

    $handler = FieldManager::fieldManager()->getFieldBuilderHandler($type);
    if ($handler instanceof FieldBuilderInterface) {
        return $handler->build(Request::createFromGlobals(),$type, $options);
    }
    return '';
}


/**
 * @return array
 */
function get_functions(): array
{
    return array(
        new \Twig\TwigFunction('get_content_type', function ($content_name) {
            return getContentType($content_name);
        }),
        new \Twig\TwigFunction('get_content_type_field', function ($content_name, $field_name) {
            return getContentTypeField($content_name, $field_name);
        }),
        new \Twig\TwigFunction('route_by_name', function ($route_name) {
            return routeByName($route_name);
        }),
        new \Twig\TwigFunction('file_uri', function ($fid) {
            return FileFunction::resolve_fid($fid);
        }),
        new \Twig\TwigFunction('file', function ($fid) {
            return FileFunction::file($fid);
        }),
        new \Twig\TwigFunction('br', function ($text,$at= 100) {
            return breakLineToHtml($text,$at);
        }),
        new \Twig\TwigFunction('url', function ($url, $options = [], $params = []) {
            return url($url, $options, $params);
        }),
        new \Twig\TwigFunction('search_form', function ($search_key, $wrapper = false) {
            return search_api($search_key,$wrapper);
        }),
        new \Twig\TwigFunction('author', function ($uid) {
            return author($uid);
        }),
        new TwigFunction('t',function(string $text, ?string $from = null, ?string $to = null){
            return t($text, $from, $to);
        }),
        new TwigFunction('translation',function(?string $code){
            return translation($code);
        }),
        new TwigFunction('getFieldTypeInfo',function(string $type){
            return getFieldTypeInfo($type);
        }),
        new TwigFunction('get_field_type_info',function(string $type, $index = 0, array $field = []){
            return get_field_type_info($type, $index, $field);
        }),
        new TwigFunction('tokens_floating_window',function(){
            return \Simp\Core\modules\tokens\TokenManager::token()->getFloatingWindow();
        })
    );
}
