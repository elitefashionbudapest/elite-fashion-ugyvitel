<?php
use App\Core\Auth;

$currentUser   = $data['currentUser'];
$conversations = $data['conversations'] ?? [];
$totalUnread   = $data['totalUnread'] ?? 0;
?>

<div class="flex h-[calc(100vh-7rem)] gap-0 md:gap-4" id="chat-app"
     data-user-id="<?= $currentUser['id'] ?>"
     data-user-name="<?= e($currentUser['name']) ?>"
     data-base-url="<?= base_url('') ?>">

    <!-- BAL PANEL: Beszélgetések -->
    <div class="w-full md:w-80 flex-shrink-0 bg-white rounded-2xl shadow-sm flex flex-col overflow-hidden" id="chat-sidebar">
        <div class="p-4 border-b border-gray-100">
            <h2 class="font-heading font-bold text-lg text-gray-900">Chat</h2>
            <?php if ($totalUnread > 0): ?>
                <span class="text-xs text-gray-500"><?= $totalUnread ?> olvasatlan</span>
            <?php endif; ?>
        </div>

        <!-- Közös chat -->
        <div class="p-2">
            <button onclick="Chat.switchConversation(null)"
                    id="conv-public"
                    class="w-full flex items-center gap-3 px-3 py-3 rounded-xl transition-colors bg-primary/10 hover:bg-primary/20 text-gray-900 conversation-item active">
                <div class="w-10 h-10 rounded-full bg-primary flex items-center justify-center flex-shrink-0">
                    <i class="fa-solid fa-comments text-sidebar text-lg"></i>
                </div>
                <div class="flex-1 text-left min-w-0">
                    <p class="font-semibold text-sm truncate">Közös chat</p>
                    <p class="text-xs text-gray-500 truncate">Mindenki látja</p>
                </div>
                <i class="fa-solid fa-chevron-right text-gray-300 text-xs md:hidden"></i>
            </button>
        </div>

        <div class="px-4 py-2">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Privát üzenetek</p>
        </div>

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
                            <p class="text-xs text-gray-400 italic mt-0.5">Nincs üzenet</p>
                        <?php endif; ?>
                    </div>
                    <i class="fa-solid fa-chevron-right text-gray-300 text-xs md:hidden"></i>
                </button>
            <?php endforeach; ?>

            <?php if (empty($conversations)): ?>
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
                        <i class="fa-solid fa-chevron-right text-gray-300 text-xs md:hidden"></i>
                    </button>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- KÖZÉPSŐ PANEL: Üzenetek -->
    <div class="hidden md:flex flex-1 bg-white rounded-2xl shadow-sm flex-col overflow-hidden" id="chat-main">
        <!-- Fejléc -->
        <div class="px-4 sm:px-6 py-3 border-b border-gray-100 flex items-center gap-3" id="chat-header">
            <button onclick="chatShowSidebar()" class="md:hidden p-2 -ml-2 text-gray-500 hover:text-gray-700">
                <i class="fa-solid fa-arrow-left"></i>
            </button>
            <div class="w-10 h-10 rounded-full bg-primary flex items-center justify-center flex-shrink-0">
                <i class="fa-solid fa-comments text-sidebar text-lg" id="chat-header-icon"></i>
            </div>
            <div>
                <h3 class="font-heading font-bold text-gray-900 text-sm sm:text-base" id="chat-header-title">Közös chat</h3>
                <p class="text-xs text-gray-500" id="chat-header-subtitle">Mindenki látja az üzeneteket</p>
            </div>
        </div>

        <!-- Üzenetek -->
        <div class="flex-1 overflow-y-auto p-3 sm:p-4 space-y-3" id="chat-messages">
            <div class="flex items-center justify-center h-full">
                <div class="text-center text-gray-400">
                    <i class="fa-regular fa-comments text-5xl mb-2"></i>
                    <p class="text-sm">Üzenetek betöltése...</p>
                </div>
            </div>
        </div>

        <!-- Küldés -->
        <div class="px-3 sm:px-4 py-2 sm:py-3 border-t border-gray-100 bg-gray-50/50">
            <form onsubmit="Chat.handleSend(event)" class="flex items-center gap-2">
                <input type="text"
                       id="chat-input"
                       placeholder="Írjon üzenetet..."
                       autocomplete="off"
                       maxlength="2000"
                       class="flex-1 rounded-xl border-gray-200 bg-white px-3 py-2.5 text-sm focus:ring-2 focus:ring-primary focus:border-primary placeholder:text-gray-400">
                <label class="cursor-pointer p-2.5 text-gray-400 hover:text-primary transition-colors flex-shrink-0">
                    <i class="fa-solid fa-camera text-lg"></i>
                    <input type="file" accept="image/*" capture="environment" class="hidden" id="chat-image-input" onchange="Chat.handleImageUpload(this)">
                </label>
                <button type="submit"
                        id="chat-send-btn"
                        class="bg-sidebar hover:bg-gray-800 text-primary rounded-xl px-4 py-2.5 font-semibold text-sm transition-colors flex-shrink-0">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<script src="<?= base_url('/assets/js/chat.js') ?>"></script>
<script>
// Mobil: sidebar/chat panel váltás
function chatShowMessages() {
    document.getElementById('chat-sidebar').classList.add('hidden');
    document.getElementById('chat-sidebar').classList.remove('flex');
    document.getElementById('chat-main').classList.remove('hidden');
    document.getElementById('chat-main').classList.add('flex');
}
function chatShowSidebar() {
    document.getElementById('chat-main').classList.add('hidden');
    document.getElementById('chat-main').classList.remove('flex');
    document.getElementById('chat-sidebar').classList.remove('hidden');
    document.getElementById('chat-sidebar').classList.add('flex');
}

// switchConversation: mobilon váltson a chat panelre
if (typeof Chat !== 'undefined') {
    const _origSwitch = Chat.switchConversation;
    Chat.switchConversation = function(userId) {
        _origSwitch(userId);
        if (window.innerWidth < 768) {
            chatShowMessages();
        }
    };
}
</script>
