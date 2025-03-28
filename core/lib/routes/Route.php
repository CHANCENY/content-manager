<?php

namespace Simp\Core\lib\routes;

class Route
{
    public string $route_id;
    public string $route_title;
    public string $route_path;
    public array $method = [];
    public string $controller;
    public string $controller_method;
    public array $access;

    public function __construct(string $route_id, array $route_data)
    {
        $this->route_id = $route_id;
        $this->route_title = $route_data['title'];
        $this->route_path = $route_data['path'];
        $this->method = $route_data['method'];
        $this->controller = $route_data['controller']['class'];
        $this->controller_method = $route_data['controller']['method'];
        $this->access = $route_data['access'] ?? [];
    }

    public function getRouteId(): string
    {
        return $this->route_id;
    }

    public function getRouteTitle(): string
    {
        return $this->route_title;
    }

    public function getRoutePath(): string
    {
        return $this->route_path;
    }

    public function getMethod(): array
    {
        return $this->method;
    }

    public function getController(): string
    {
        return $this->controller;
    }

    public function getControllerMethod(): string
    {
        return $this->controller_method;
    }

    public function getAccess(): array
    {
        return $this->access;
    }

    public function __get(string $name)
    {
        return match ($name) {
            'access' => $this->access,
            'controller', 'class' => $this->controller,
            'controller_method' => $this->controller_method,
            'method', 'methods' => $this->method,
            'route_id', 'id' => $this->route_id,
            'route_title', 'title' => $this->route_title,
            'route_path', 'path' => $this->route_path,
            default => null,
        };
    }

}