<?php

use Simp\Core\extends\announcement\src\controller\AnnouncementController;
use Simp\Core\modules\database\Database;

function announcement_route_install(): array
{
    return [
        'announcement.notifications' => [
            'title' => 'Announcement Notifications',
            'path' => '/admin/announcement/notifications',
            'method' => [
                'GET',
                'POST'
            ],
            'controller' => [
                'class' => AnnouncementController::class,
                'method' => 'announcement_notifications'
            ],
            'access' => [
                'administrator'
            ]
        ]
    ];
}

function announcement_database_install(): bool
{
    $query = "CREATE TABLE IF NOT EXISTS `announcement` (id INT AUTO_INCREMENT PRIMARY KEY, 
title VARCHAR(255), 
owner_uid INT NOT NULL,
 to_uid INT NOT NULL, 
 content TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `announcement_owner_uid` FOREIGN KEY (`owner_uid`) REFERENCES `users` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE)";

    $query = Database::database()->con()->prepare($query);
    return $query->execute();
}