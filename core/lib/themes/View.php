<?php

namespace Simp\Core\lib\themes;

use Phpfastcache\Exceptions\PhpfastcacheCoreException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Simp\Core\lib\memory\cache\Caching;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class View
{
    /**
     * @var Theme|mixed
     */
    protected Theme $theme;

    public function __construct() {
        $this->theme = new Theme();
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function render(string $view, array $data = []): string {
        $options = [...$this->theme->getOptions(), ...$data];
        return $this->theme->twig->render($view,$options);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public static function view(string $view, array $data = []): string
    {
        return (new self())->render($view, $data);
    }
}