<?php

namespace App\Controllers;

use App\Core\{Auth, Middleware};
use App\Models\Notification;

class NotificationController
{
    public function index(): void
    {
        Middleware::auth();

        $notifications = Notification::getForUser(Auth::id(), 100);

        view('layouts/app', [
            'content' => 'notifications/index',
            'data' => [
                'pageTitle'     => 'Értesítések',
                'activeTab'     => '',
                'notifications' => $notifications,
            ]
        ]);
    }

    /**
     * JSON: olvasatlan értesítések (header csengő ikonhoz)
     */
    public function apiUnread(): void
    {
        Middleware::auth();

        header('Content-Type: application/json');
        $unread = Notification::getUnread(Auth::id(), 10);
        $count = Notification::getUnreadCount(Auth::id());

        echo json_encode(['count' => $count, 'items' => $unread]);
    }

    /**
     * Értesítés olvasottnak jelölése
     */
    public function markRead(string $id): void
    {
        Middleware::auth();
        Middleware::verifyCsrf();

        Notification::markAsRead((int)$id);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            return;
        }

        redirect('/notifications');
    }
}
