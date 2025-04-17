<?php

namespace Simp\Core\lib\controllers;

use Simp\Core\lib\routes\Route;
use Simp\Core\modules\integration\rest\JsonRestManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class JsonRestController
{
    public function handle_api_request(...$args): JsonResponse
    {
        extract($args);

        /**@var $route Route */
        $route = $args['options']['route'] ?? null;

        /**@var $request Request**/
        if ($route !== null) {


            $setting = JsonRestManager::factory()->getVersionRoutePostSetting($route->route_id);
            if ($request->getMethod() === 'POST') {
                return new JsonResponse($setting);
            }

            return new JsonResponse(['status'=>true]);
        }
        return new JsonResponse(['error' => 'endpoint not found']);
    }
}