<?php

namespace Simp\Core\lib\themes;

use Phpfastcache\Exceptions\PhpfastcacheCoreException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Simp\Core\lib\installation\SystemDirectory;
use Simp\Core\lib\memory\cache\Caching;
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
        $this->twig_function_definition_file = $this->setting_dir .DIRECTORY_SEPARATOR . 'twig'.DIRECTORY_SEPARATOR.'functions.php';

        $this->twig_functions =file_exists($this->twig_function_definition_file) ? require_once $this->twig_function_definition_file : [];
        //TODO: add more options for twig eg current_user info, route info.
        $this->options = [
            'page_title' => 'Simple Content Management System',
            'page_description' => 'Simple Content Management System',
            'page_keywords' => 'Content, Management, System',
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
        $twig_options = Yaml::parseFile($this->schema_dir.DIRECTORY_SEPARATOR.'manifest.yml')['twig_setting'] ?? [];
        if ($twig_options) {
            foreach ($twig_options as $key => $value) {
                if ($key === 'cache' && $value === true) {
                    $twig_options[$key] = $this->var_dir .DIRECTORY_SEPARATOR . 'twig'.DIRECTORY_SEPARATOR.'cache';
                }
            }
        }

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