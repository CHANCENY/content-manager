<?php

namespace Simp\Core\lib\middlewares;

use Simp\Core\lib\middlewares\trait\Middleware;
use Simp\Core\modules\messager\Messager;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AuthenticatedMiddleware extends Middleware
{
    public function __construct()
    {
        parent::__construct();


        if (empty($this->user) || ($this->user?->isIsAdmin() === false && $this->user?->isIsAuthenticated() === false)) {
            $redirect = new RedirectResponse('/');
            $redirect->setStatusCode(302);
            Messager::toast()->addInfo("You are not authorized to access this page.");
            $redirect->send();
        }
    }
}