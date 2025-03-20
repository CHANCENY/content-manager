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

}