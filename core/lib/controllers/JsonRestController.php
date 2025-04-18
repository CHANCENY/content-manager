<?php

namespace Simp\Core\lib\controllers;

use Simp\Core\components\rest_data_source\interface\RestDataSourceInterface;
use Simp\Core\lib\routes\Route;
use Simp\Core\modules\integration\rest\JsonRestManager;
use Simp\Core\modules\logger\ErrorLogger;
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
            try {
                $handler = JsonRestManager::factory()->getVersionRouteDataSourceSetting($route->route_id);
                $handler = new $handler($route, $args);
                if ($handler instanceof RestDataSourceInterface) {
                    return new JsonResponse($handler->getResponse(), $handler->getStatusCode());
                }
                throw new \Exception('Handler not found  or handle has not implemented RestDataSourceInterface');
            }catch (\Throwable $exception) {
                ErrorLogger::logger()->logError($exception->getMessage());
            }

            return new JsonResponse(['status'=>true]);
        }
        return new JsonResponse(['error' => 'Endpoint not found']);
    }
}