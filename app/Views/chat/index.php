<?php
use App\Core\Auth;

$currentUser   = $data['currentUser'];
$conversations = $data['conversations'] ?? [];
$totalUnread   = $data['totalUnread'] ?? 0;
?>

<!-- Chat kontener - teljes magassag a main-en belul -->
<div class="flex h-[calc(100vh-7rem)] gap-4" id="chat-app"
     data-user-id="<?= $currentUser['id'] ?>"
     data-user-name="<?= e($currentUser['name']) ?>"
     data-base-url="<?= base_url('') ?>">

    <!-- BAL PANEL: Beszelgetesek -->
    <div class="w-80 flex-shrink-0 bg-white rounded-2xl shadow-sm flex flex-col overflow-hidden">
        <!-- Fejlec -->
        <div class="p-4 border-b border-gray-100">
            <h2 class="font-heading font-bold text-lg text-gray-900">Chat</h2>
            <?php if ($totalUnread > 0): ?>
                <span class="text-xs text-gray-500"><?= $totalUnread ?> olvasatlan</span>
            <?php endif; ?>
        </div>

        <!-- Kozos chat gomb -->
        <div class="p-2">
            <button onclick="Chat.switchConversation(null)"
                    id="conv-public"
                    class="w-full flex items-center gap-3 px-3 py-3 rounded-xl transition-colors bg-primary/10 hover:bg-primary/20 text-gray-900 conversation-item active">
                <div class="w-10 h-10 rounded-full bg-primary flex items-center justify-center flex-shrink-0">
                    <i class="fa-solid fa-comments text-sidebar text-lg"></i>
                </div>
                <div class="flex-1 text-left min-w-0">
                    <p class="font-semibold text-sm truncate">Kozos chat</p>
                    <p class="text-xs text-gray-500 truncate">Mindenki latja</p>
                </div>
            </button>
        </div>

        <!-- Elvalaszto -->
        <div class="px-4 py-2">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Privat uzenetek</p>
        </div>

        <!-- Felhasznalok listaja -->
        <div class="flex-1 overflow-y-auto px-2 pb-2 space-y-1" id="conversation-list">
            <?php foreach ($conversations as $conv): ?>
                <button onclick="Chat.switchConversation(<?= $conv['user_id'] ?>)"
                        id="conv-<?= $conv['user_id'] ?>"
                        class="w-full flex items-center gap-3 px-3 py-3 rounded-xl transition-colors hover:bg-gray-50 text-gray-900 conversation-item">
                    <div class="w-10 h-10 rounded-full bg-surface flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-user text-gray-500 text-lg"></i>
                    </div>
                    <div class="flex-1 text-left min-w-0">
                        <div class="flex items-center justify-between">
                            <p class="font-semibold text-sm truncate"><?= e($conv['user_name']) ?></p>
                            <?php if ($conv['unread_count'] > 0): ?>
                                <span class="unread-badge ml-2 bg-red-500 text-white text-[10px] font-bold rounded-full w-5 h-5 flex items-center justify-center flex-shrink-0">
                                    <?= $conv['unread_count'] > 9 ? '9+' : $conv['unread_count'] ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php if ($conv['last_message']): ?>
                            <p class="text-xs text-gray-500 truncate mt-0.5"><?= e(mb_substr($conv['last_message'], 0, 40)) ?></p>
                        <?php else: ?>
                            <p class="text-xs text-gray-400 italic mt-0.5">Nincs uzenet</p>
                        <?php endif; ?>
                    </div>
                </button>
            <?php endforeach; ?>

            <?php if (empty($conversations)): ?>
                <!-- Osszes felhasznalo megjelenites ha meg nincs beszelgetes -->
                <?php foreach ($data['users'] as $user): ?>
                    <button onclick="Chat.switchConversation(<?= $user['id'] ?>)"
                            id="conv-<?= $user['id'] ?>"
                            class="w-full flex items-center gap-3 px-3 py-3 rounded-xl transition-colors hover:bg-gray-50 text-gray-900 conversation-item">
                        <div class="w-10 h-10 rounded-full bg-surface flex items-center justify-center flex-shrink-0">
                            <i class="fa-solid fa-user text-gray-500 text-lg"></i>
                        </div>
                        <div class="flex-1 text-left min-w-0">
                            <p class="font-semibold text-sm truncate"><?= e($user['name']) ?></p>
                            <p class="text-xs text-gray-400 italic mt-0.5">
                                <?= $user['role'] === 'tulajdonos' ? 'Tulajdonos' : e($user['store_name'] ?? 'Bolt') ?>
                            </p>
                        </div>
                    </button>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- KOZEPSO PANEL: Uzenetek -->
    <div class="flex-1 bg-white rounded-2xl shadow-sm flex flex-col overflow-hidden">
        <!-- Chat fejlec -->
        <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3" id="chat-header">
            <div class="w-10 h-10 rounded-full bg-primary flex items-center justify-center flex-shrink-0">
                <i class="fa-solid fa-comments text-sidebar text-lg" id="chat-header-icon"></i>
            </div>
            <div>
                <h3 class="font-heading font-bold text-gray-900" id="chat-header-title">Kozos chat</h3>
                <p class="text-xs text-gray-500" id="chat-header-subtitle">Mindenki latja az uzeneteket</p>
            </div>
        </div>

        <!-- Uzenetek terulet -->
        <div class="flex-1 overflow-y-auto p-4 space-y-3" id="chat-messages">
            <div class="flex items-center justify-center h-full">
                <div class="text-center text-gray-400">
                    <i class="fa-regular fa-comments text-5xl mb-2"></i>
                    <p class="text-sm">Uzenetek betoltese...</p>
                </div>
            </div>
        </div>

        <!-- Uzenet kuldese -->
        <div class="px-4 py-3 border-t border-gray-100 bg-gray-50/50">
            <form onsubmit="Chat.handleSend(event)" class="flex items-center gap-3">
                <input type="text"
                       id="chat-input"
                       placeholder="Irjon uzenetet..."
                       autocomplete="off"
                       maxlength="2000"
                       class="flex-1 rounded-xl border-gray-200 bg-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary transition-shadow placeholder:text-gray-400">
                <button type="submit"
                        id="chat-send-btn"
                        class="bg-sidebar hover:bg-gray-800 text-primary rounded-xl px-5 py-2.5 font-semibold text-sm transition-colors flex items-center gap-2">
                    <i class="fa-solid fa-paper-plane text-base"></i>
                    Kuldes
                </button>
            </form>
        </div>
    </div>
</div>

<script src="<?= base_url('/assets/js/chat.js') ?>"></script>
