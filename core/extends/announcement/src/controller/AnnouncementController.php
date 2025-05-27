<?php

namespace Simp\Core\extends\announcement\src\controller;

use Symfony\Component\HttpFoundation\Response;

class AnnouncementController
{
    public function announcement_notifications(...$args): Response
    {
        return new Response('Announcement Notifications');
    }
}