<?php

// Declaration of need hooks for creating auto path schema and templates

use Simp\Core\components\extensions\ModuleHandler;
use Simp\Core\extends\auto_path\src\controller\AutoPathController;
use Simp\Core\modules\database\Database;

function auto_path_database_install(): bool
{
    $query = "CREATE TABLE IF NOT EXISTS `auto_path` (id INT AUTO_INCREMENT PRIMARY KEY, path VARCHAR(255) NOT NULL UNIQUE, nid INT NOT NULL UNIQUE, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, CONSTRAINT fk_nid FOREIGN KEY (nid) REFERENCES node_data(nid) ON DELETE CASCADE)";
    return Database::database()->con()->prepare($query)->execute();
}

function auto_path_template_install(): array {
    $module = ModuleHandler::factory()->getModule('auto_path');
    $path = $module['path'] ?? __DIR__;
    return [
        $path . DIRECTORY_SEPARATOR . 'templates'
    ];
}

function auto_path_route_install(): array
{
    return [
        'auto_path.create' => [
            'title' => 'Auto Path',
            'path' => '/admin/auto-path/create',
            'method' => [
                'GET',
                'POST'
            ],
            'controller' => [
                'class' => AutoPathController::class,
                'method' => 'auto_path_create'
            ],
            'access' => [
                'administrator',
            ]
        ]
    ];

}