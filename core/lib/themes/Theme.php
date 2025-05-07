<?php

namespace Simp\Core\lib\themes;

use Phpfastcache\Exceptions\PhpfastcacheCoreException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Simp\Core\lib\installation\SystemDirectory;
use Simp\Core\lib\memory\cache\Caching;
use Simp\Core\modules\assets_manager\AssetsManager;
use Simp\Core\modules\config\ConfigManager;
use Simp\Core\modules\user\current_user\CurrentUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Yaml;
use Twig\Environment;
use Twig\TwigFunction;

class Theme extends SystemDirectory
{
    protected array $twig_functions;
    protected string $twig_function_definition_file;
    protected array $options;
    public readonly Environment $twig;

    /**
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct();
        $default_twig_function = Caching::init()->get('default.admin.functions');
        $default_twig_function_array = [];
        if (!empty($default_twig_function) && file_exists($default_twig_function)) {
            $default_twig_function_array = require_once $default_twig_function;
            if (!is_array($default_twig_function_array)) {
                $default_twig_function_array = [];
            }
        }

        $this->twig_function_definition_file = $this->setting_dir .DIRECTORY_SEPARATOR . 'twig'.DIRECTORY_SEPARATOR.'functions.php';
        $custom_functions = file_exists($this->twig_function_definition_file) ? require_once $this->twig_function_definition_file : [];
        $this->twig_functions = [...(is_array($default_twig_function_array) ? $default_twig_function_array : []),
            ...(is_array($custom_functions) ? $custom_functions : [])];
        $site = ConfigManager::config()->getConfigFile('basic.site.setting');
        $this->options = [
            'page_title' => $site?->get('site_name'),
            'page_description' => $site?->get('site_slogan'),
            'page_keywords' => 'Content, Management, System',
            'request' => [
                'user' => CurrentUser::currentUser(),
                'http' => Request::createFromGlobals()
            ],
            'site' => $site,
            'assets' => new AssetsManager(),
        ];
        $twig_views = [];
        $theme_keys = Caching::init()->get("system.theme.keys") ?? [];

        if ($theme_keys) {
            foreach ($theme_keys as $theme) {
                if (Caching::init()->has($theme)) {
                    $template = Caching::init()->get($theme);
                    if ($template instanceof TwigResolver) {
                        $twig_views[$theme] = $template->__toString();
                    }
                }
            }
        }

        $loader = new \Twig\Loader\ArrayLoader($twig_views);
        //$twig_options = Yaml::parseFile($this->schema_dir.DIRECTORY_SEPARATOR.'manifest.yml')['twig_setting'] ?? [];
        $twig_options = [
            'debug' => true,
            'cache' => false,
            'strict_variables' => FALSE,
            'charset' => 'UTF-8',
        ];

        $this->twig_functions[] = new TwigFunction('dump', function ($var): void {
            dump($var);
        });
        $this->twig_functions[] = new TwigFunction('dd', function ($asset): void {
            dd($asset);
        });

        $this->twig = new \Twig\Environment($loader, [
            ...$twig_options,
        ]);

        if (!empty($this->twig_functions)) {
            foreach ($this->twig_functions as $function) {
                if ($function instanceof TwigFunction) {
                    $this->twig->addFunction($function);
                }
            }
        }
    }
    public function getTwigFunctions(): array
    {
        return $this->twig_functions;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

}