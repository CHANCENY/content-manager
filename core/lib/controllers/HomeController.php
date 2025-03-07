<?php

namespace Simp\Core\lib\controllers;

use Exception;
use Simp\Core\lib\themes\View;
use Symfony\Component\HttpFoundation\Response;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class HomeController
{
    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     * @throws Exception
     */
    public function home_controller(...$args): Response
    {
        // dump(CurrentUser::currentUser());
        return new Response(View::view('default.view.home'),200);
    }
}