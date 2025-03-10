<?php

namespace Simp\Core\lib\middlewares;

use Phpfastcache\Exceptions\PhpfastcacheCoreException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheIOException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Simp\Core\lib\middlewares\trait\Middleware;
use Simp\Core\modules\messager\Messager;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AdminMiddleware extends Middleware
{
    /**
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheIOException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct();
        if (empty($this->user) || !$this->user?->isIsAdmin()) {
            $redirect = new RedirectResponse('/');
            $redirect->setStatusCode(302);
            Messager::toast()->addInfo("You are not authorized to access this page.");
            $redirect->send();
        }

    }
}