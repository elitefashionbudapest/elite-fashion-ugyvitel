<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Middleware;
use App\Models\ChatMessage;
use App\Models\User;

class ChatController
{
    /**
     * Chat fooldal megjelenites
     */
    public function index(): void
    {
        Middleware::auth();

        $currentUser = Auth::user();
        $users = User::all();

        // Kiszurjuk az aktualis felhasznalot a listabol
        $otherUsers = array_filter($users, fn($u) => $u['id'] !== $currentUser['id']);

        // Beszelgetesek listaja olvasatlan szamlaloval
        $conversations = ChatMessage::getConversations($currentUser['id']);

        // Osszes olvasatlan privat uzenet szama
        $totalUnread = ChatMessage::getUnreadCount($currentUser['id']);

        $data = [
            'pageTitle'     => 'Chat',
            'activeTab'     => 'chat',
            'currentUser'   => $currentUser,
            'users'         => array_values($otherUsers),
            'conversations' => $conversations,
            'totalUnread'   => $totalUnread,
        ];

        view('layouts/app', ['content' => 'chat/index', 'data' => $data]);
    }

    /**
     * Uzenetek lekerese (JSON)
     * GET params: type (public|private), user_id, before_id
     */
    public function getMessages(): void
    {
        Middleware::auth();

        $type     = $_GET['type'] ?? 'public';
        $userId   = isset($_GET['user_id']) ? (int) $_GET['user_id'] : null;
        $beforeId = isset($_GET['before_id']) ? (int) $_GET['before_id'] : null;
        $currentUserId = Auth::id();

        if ($type === 'private' && $userId) {
            $messages = ChatMessage::getPrivateMessages($currentUserId, $userId, 50, $beforeId);

            // Olvasottra jeloljuk a masik felhasznalo uzeneteit
            ChatMessage::markAsRead($currentUserId, $userId);
        } else {
            $messages = ChatMessage::getPublicMessages(50, $beforeId);
        }

        // Kronologikus sorrend (legujabb alul)
        $messages = array_reverse($messages);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success'  => true,
            'messages' => $messages,
            'user_id'  => $currentUserId,
        ]);
        exit;
    }

    /**
     * Uzenet kuldese (JSON)
     * POST: message, receiver_id (null = publikus)
     */
    public function send(): void
    {
        Middleware::auth();
        Middleware::verifyCsrf();

        $input = json_decode(file_get_contents('php://input'), true);

        $message    = trim($input['message'] ?? '');
        $receiverId = isset($input['receiver_id']) ? (int) $input['receiver_id'] : null;

        if ($message === '') {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Az uzenet nem lehet ures.']);
            exit;
        }

        // Max uzenet hossz
        if (mb_strlen($message) > 2000) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Az uzenet maximum 2000 karakter lehet.']);
            exit;
        }

        $senderId  = Auth::id();
        $messageId = ChatMessage::send($senderId, $receiverId, $message);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success'    => true,
            'message_id' => $messageId,
        ]);
        exit;
    }

    /**
     * Uzenetek olvasottra allitasa (JSON)
     * POST: sender_id
     */
    public function markRead(): void
    {
        Middleware::auth();
        Middleware::verifyCsrf();

        $input    = json_decode(file_get_contents('php://input'), true);
        $senderId = isset($input['sender_id']) ? (int) $input['sender_id'] : null;

        if ($senderId) {
            ChatMessage::markAsRead(Auth::id(), $senderId);
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true]);
        exit;
    }
}
