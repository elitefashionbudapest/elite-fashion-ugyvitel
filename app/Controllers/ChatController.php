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

        // JSON vagy form-data támogatás
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
        } else {
            $input = $_POST;
        }

        $message    = trim($input['message'] ?? '');
        $receiverId = isset($input['receiver_id']) && $input['receiver_id'] !== '' ? (int) $input['receiver_id'] : null;

        // Kép feltöltés ellenőrzése
        $imagePath = null;
        if (!empty($_FILES['chat_image']['tmp_name'])) {
            $imagePath = $this->handleChatImage();
        }

        if ($message === '' && !$imagePath) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Az üzenet nem lehet üres.']);
            exit;
        }

        if (mb_strlen($message) > 2000) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Az üzenet maximum 2000 karakter lehet.']);
            exit;
        }

        $senderId  = Auth::id();
        $messageId = ChatMessage::send($senderId, $receiverId, $message ?: '', $imagePath);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success'    => true,
            'message_id' => $messageId,
        ]);
        exit;
    }

    private function handleChatImage(): ?string
    {
        $file = $_FILES['chat_image'];
        if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024) return null;

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
        if (!isset($allowedMimes[$mime])) return null;

        $ext = $allowedMimes[$mime];
        $uploadDir = __DIR__ . '/../../public/uploads/chat/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
            return '/uploads/chat/' . $filename;
        }
        return null;
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
