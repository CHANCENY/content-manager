<?php

namespace Simp\Core\lib\controllers;

use Phpfastcache\Exceptions\PhpfastcacheCoreException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheIOException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Simp\Core\modules\messager\Messager;
use Simp\Core\modules\structures\views\Display;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class ViewsController
{
    /**
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheIOException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function views_entry_controller(...$args): Response|RedirectResponse|JsonResponse
    {
        extract($args);
        $route_key = $options['key'] ?? null;
        if (empty($route_key)) {
            Messager::toast()->addError("Page Not Found");
            return new RedirectResponse('/');
        }
        $display = Display::display($route_key);

        if (!$display->isDisplayExists() || !$display->isViewExists()) {
            Messager::toast()->addError("Page Not Found");
            return new RedirectResponse('/');
        }

        if (!$display->isAccessible()) {
            Messager::toast()->addError("Access Denied");
            return new RedirectResponse('/');
        }
        $query = $display->prepareQuery();
        dump($query);
        return new Response("hello world");
    }
}