<?php

namespace Simp\Core\lib\controllers;

use Phpfastcache\Exceptions\PhpfastcacheCoreException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheIOException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Simp\Core\lib\memory\cache\Caching;
use Simp\Core\lib\themes\View;
use Simp\Core\modules\messager\Messager;
use Simp\Core\modules\structures\content_types\ContentDefinitionManager;
use Simp\Core\modules\structures\content_types\helper\NodeFunction;
use Simp\Core\modules\structures\views\Display;
use Simp\Core\modules\user\current_user\CurrentUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class ViewsController
{
    use NodeFunction;
    /**
     * @throws RuntimeError
     * @throws PhpfastcacheIOException
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws LoaderError
     * @throws SyntaxError
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheDriverException
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
        $display->prepareQuery($request);
        $display->runDisplayQuery();
        $display_settings = $display->getDisplay();

        $view_rows = [];
        foreach ($display_settings['fields'] as $field) {
            $content_type = $field['content_type'] === 'node' ? null : ContentDefinitionManager::contentDefinitionManager()
                ->getContentType($field['content_type']);

            $type = $field['content_type'];

            if (!empty($content_type)) {
                $field = self::findField($content_type['fields'], $field['field']);
            }
            $view_rows[] = [
                'name' => $field['name'] ?? $field['field'],
                'label' => $field['label'] ?? ucfirst($field['field']),
                'content_type' => $type,
            ];
        }

        rsort($view_rows);

        $response_type = $display_settings['response_type'] ?? 'application/json';
        if ($response_type === 'text/html') {

            $view_template = $display_settings['view'] .'_'. $display_settings['display_name'];
            if (Caching::init()->has($view_template)) {
                return new Response(View::view($view_template,['content'=> $display->getViewDisplayResults()]));
            }
            return new Response(View::view('default.view.view.results.rows',
                [
                    'content'=> $display->getViewDisplayResults(),
                    'fields' => $view_rows,
                    'is_admin' => CurrentUser::currentUser()?->isIsAdmin(),
                    'query' => $display->getViewDisplayQuery(),
                    'display' => $display
                ]
            )
            );

        }

        elseif ($response_type === 'application/json') {
            return new JsonResponse($display->getRawResults());
        }

        return new JsonResponse("hellp");
    }
}