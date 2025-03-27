<?php

namespace Simp\Core\lib\controllers;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class ViewsController
{
    public function views_entry_controller(...$args): Response|RedirectResponse|JsonResponse
    {
        return new Response("hello world");
    }
}