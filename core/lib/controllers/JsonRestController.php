<?php

namespace Simp\Core\lib\controllers;

use Symfony\Component\HttpFoundation\JsonResponse;

class JsonRestController
{
    public function handle_api_request(...$args): JsonResponse
    {
        return new JsonResponse(['status' => 'ok']);
    }
}