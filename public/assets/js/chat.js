/**
 * Elite Fashion Chat modul
 * AJAX polling chat - kesobb WebSocket-re frissitheto
 */
const Chat = (function () {
    'use strict';

    // Allapot
    let currentConversation = null; // null = publikus, userId = privat
    let currentConversationName = 'Kozos chat';
    let pollInterval = null;
    let isLoading = false;
    let lastMessageId = null;
    let forceScrollBottom = false;

    // DOM elemek
    const app = document.getElementById('chat-app');
    const messagesContainer = document.getElementById('chat-messages');
    const chatInput = document.getElementById('chat-input');
    const headerTitle = document.getElementById('chat-header-title');
    const headerSubtitle = document.getElementById('chat-header-subtitle');
    const headerIcon = document.getElementById('chat-header-icon');

    // Konfiguracio
    const currentUserId = app ? parseInt(app.dataset.userId) : 0;
    const currentUserName = app ? app.dataset.userName : '';
    const baseUrl = app ? app.dataset.baseUrl : '';
    const POLL_INTERVAL_MS = 500;

    /**
     * Inicializalas
     */
    function init() {
        if (!app) return;

        // Publikus chattal indulunk
        loadMessages();
        startPolling();

        // Enter billentyuvel kuldes
        if (chatInput) {
            chatInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
        }

        // Mobilon hosszú nyomás = context menu
        var longPressTimer = null;
        if (messagesContainer) {
            messagesContainer.addEventListener('touchstart', function(e) {
                var msgEl = e.target.closest('.chat-own-msg');
                if (!msgEl) return;
                longPressTimer = setTimeout(function() {
                    var touch = e.touches[0];
                    var fakeEvent = { preventDefault: function(){}, clientX: touch.clientX, clientY: touch.clientY };
                    showContextMenu(fakeEvent, parseInt(msgEl.dataset.msgId));
                }, 500);
            });
            messagesContainer.addEventListener('touchend', function() {
                clearTimeout(longPressTimer);
            });
            messagesContainer.addEventListener('touchmove', function() {
                clearTimeout(longPressTimer);
            });
        }
    }

    /**
     * Beszelgetes valtas
     */
    function switchConversation(userId) {
        currentConversation = userId;
        lastMessageId = null;

        // Aktiv jeloles frissites
        document.querySelectorAll('.conversation-item').forEach(function (el) {
            el.classList.remove('active', 'bg-primary/10');
            el.classList.add('hover:bg-gray-50');
        });

        var activeEl;
        if (userId === null) {
            activeEl = document.getElementById('conv-public');
            currentConversationName = 'Kozos chat';
            headerTitle.textContent = 'Kozos chat';
            headerSubtitle.textContent = 'Mindenki latja az uzeneteket';
            headerIcon.className = 'fa-solid fa-comments text-sidebar text-lg';
        } else {
            activeEl = document.getElementById('conv-' + userId);
            currentConversationName = activeEl
                ? activeEl.querySelector('.font-semibold').textContent.trim()
                : 'Privat';
            headerTitle.textContent = currentConversationName;
            headerSubtitle.textContent = 'Privat beszelgetes';
            headerIcon.className = 'fa-solid fa-user text-sidebar text-lg';

            // Olvasatlan badge eltavolitasa
            if (activeEl) {
                var badge = activeEl.querySelector('.unread-badge');
                if (badge) badge.remove();
            }

            // Olvasottra jeloles a szerveren
            markAsRead(userId);
        }

        if (activeEl) {
            activeEl.classList.add('active', 'bg-primary/10');
            activeEl.classList.remove('hover:bg-gray-50');
        }

        // Uzenetek betoltese
        forceScrollBottom = true;
        loadMessages();

        // Input fokuszba
        if (chatInput) chatInput.focus();
    }

    /**
     * Uzenetek betoltese
     */
    function loadMessages(append) {
        if (isLoading) return;
        isLoading = true;

        var url = baseUrl + '/chat/messages?type=' + (currentConversation ? 'private' : 'public');
        if (currentConversation) {
            url += '&user_id=' + currentConversation;
        }

        fetch(url, {
            headers: {
                'X-CSRF-TOKEN': getCsrfToken()
            }
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.success && data.messages) {
                    renderMessages(data.messages);
                }
            })
            .catch(function (err) {
                console.error('Chat betoltes hiba:', err);
            })
            .finally(function () {
                isLoading = false;
            });
    }

    /**
     * Uzenetek megjelenites
     */
    function renderMessages(messages) {
        if (!messagesContainer) return;

        if (messages.length === 0) {
            messagesContainer.innerHTML =
                '<div class="flex items-center justify-center h-full">' +
                '<div class="text-center text-gray-400">' +
                '<i class="fa-regular fa-comments text-5xl mb-2"></i>' +
                '<p class="text-sm">Meg nincsenek uzenetek. Irjon elsokent!</p>' +
                '</div></div>';
            lastMessageId = null;
            return;
        }

        // Ellenorizzuk, hogy van-e uj uzenet
        var newestId = messages[messages.length - 1].id;
        if (newestId === lastMessageId) {
            return; // Nincs valtozas
        }

        // Mielőtt újrarajzolunk: a felhasználó alul van-e (nem scrollozott fel)?
        var isAtBottom = messagesContainer.scrollHeight - messagesContainer.scrollTop - messagesContainer.clientHeight < 80;

        lastMessageId = newestId;

        var html = '';
        var prevDate = '';

        messages.forEach(function (msg) {
            var isMine = parseInt(msg.sender_id) === currentUserId;
            var msgDate = formatDate(msg.created_at);
            var msgTime = formatTime(msg.created_at);

            // Datum elvalaszto
            if (msgDate !== prevDate) {
                html += '<div class="flex items-center justify-center my-4">' +
                    '<span class="px-3 py-1 bg-gray-100 rounded-full text-[11px] text-gray-500 font-medium">' +
                    escapeHtml(msgDate) + '</span></div>';
                prevDate = msgDate;
            }

            if (isMine) {
                // Sajat uzenet - jobb oldalon, jobb klikkel visszavonás
                html += '<div class="flex justify-end">' +
                    '<div class="max-w-[70%]">' +
                    '<div class="bg-sidebar text-white rounded-2xl rounded-br-md px-4 py-2.5 cursor-pointer chat-own-msg" data-msg-id="' + msg.id + '" oncontextmenu="Chat.showContextMenu(event, ' + msg.id + ')">' +
                    '<p class="text-sm whitespace-pre-wrap break-words">' + escapeHtml(msg.message) + '</p>' +
                    '</div>' +
                    '<p class="text-[10px] text-gray-400 mt-1 text-right">' + escapeHtml(msgTime) + '</p>' +
                    '</div></div>';
            } else {
                // Mas uzenete - bal oldalon
                html += '<div class="flex justify-start">' +
                    '<div class="max-w-[70%]">' +
                    '<p class="text-[11px] font-semibold text-gray-500 mb-1 ml-1">' +
                    escapeHtml(msg.sender_name) + '</p>' +
                    '<div class="bg-gray-100 text-gray-900 rounded-2xl rounded-bl-md px-4 py-2.5">' +
                    '<p class="text-sm whitespace-pre-wrap break-words">' + escapeHtml(msg.message) + '</p>' +
                    '</div>' +
                    '<p class="text-[10px] text-gray-400 mt-1 ml-1">' + escapeHtml(msgTime) + '</p>' +
                    '</div></div>';
            }
        });

        messagesContainer.innerHTML = html;
        if (isAtBottom || forceScrollBottom) {
            scrollToBottom();
            forceScrollBottom = false;
        }
    }

    /**
     * Uzenet kuldese
     */
    function sendMessage() {
        if (!chatInput) return;

        var message = chatInput.value.trim();
        if (message === '') return;

        var payload = {
            message: message,
            receiver_id: currentConversation
        };

        chatInput.value = '';
        chatInput.focus();

        fetchWithCsrf(baseUrl + '/chat/send', {
            method: 'POST',
            body: JSON.stringify(payload)
        })
            .then(function (data) {
                if (data.success) {
                    lastMessageId = null;
                    forceScrollBottom = true;
                    loadMessages();
                }
            })
            .catch(function (err) {
                console.error('Kuldes hiba:', err);
                // Visszatesszuk az uzenetet az input mezobe
                chatInput.value = message;
            });
    }

    /**
     * Jobb klikk context menu megjelenítés
     */
    function showContextMenu(event, messageId) {
        event.preventDefault();
        hideContextMenu();

        var menu = document.createElement('div');
        menu.id = 'chat-context-menu';
        menu.className = 'fixed z-50 bg-white rounded-xl shadow-lg border border-gray-200 py-1 min-w-[160px]';
        menu.style.left = event.clientX + 'px';
        menu.style.top = event.clientY + 'px';
        menu.innerHTML =
            '<button onclick="Chat.deleteMessage(' + messageId + ')" class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors">' +
            '<i class="fa-solid fa-rotate-left"></i> Üzenet visszavonása</button>';

        document.body.appendChild(menu);

        // Képernyőn kívülre ne lógjon
        var rect = menu.getBoundingClientRect();
        if (rect.right > window.innerWidth) menu.style.left = (window.innerWidth - rect.width - 8) + 'px';
        if (rect.bottom > window.innerHeight) menu.style.top = (window.innerHeight - rect.height - 8) + 'px';

        // Kattintásra bezáródik
        setTimeout(function() {
            document.addEventListener('click', hideContextMenu, { once: true });
        }, 0);
    }

    function hideContextMenu() {
        var existing = document.getElementById('chat-context-menu');
        if (existing) existing.remove();
    }

    /**
     * Uzenet visszavonasa (torlese)
     */
    function deleteMessage(messageId) {
        hideContextMenu();

        fetchWithCsrf(baseUrl + '/chat/delete', {
            method: 'POST',
            body: JSON.stringify({ message_id: messageId })
        })
            .then(function (data) {
                if (data.success) {
                    lastMessageId = null;
                    loadMessages();
                }
            })
            .catch(function (err) {
                console.error('Visszavonas hiba:', err);
            });
    }

    /**
     * Olvasottra jeloles
     */
    function markAsRead(senderId) {
        fetchWithCsrf(baseUrl + '/chat/mark-read', {
            method: 'POST',
            body: JSON.stringify({ sender_id: senderId })
        }).catch(function (err) {
            console.error('Olvasottra jeloles hiba:', err);
        });
    }

    /**
     * Polling inditas
     */
    function startPolling() {
        if (pollInterval) clearInterval(pollInterval);
        pollInterval = setInterval(function () {
            loadMessages();
        }, POLL_INTERVAL_MS);
    }

    /**
     * Polling leallitas (takaritas)
     */
    function stopPolling() {
        if (pollInterval) {
            clearInterval(pollInterval);
            pollInterval = null;
        }
    }

    /**
     * Form submit kezeles
     */
    function handleSend(event) {
        event.preventDefault();
        sendMessage();
    }

    /**
     * Gorgetés lefelé
     */
    function scrollToBottom() {
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
    }

    // ==========================================
    // Segedfuggvenyek
    // ==========================================

    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        var d = new Date(dateStr);
        var now = new Date();
        var today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        var msgDay = new Date(d.getFullYear(), d.getMonth(), d.getDate());

        if (msgDay.getTime() === today.getTime()) {
            return 'Ma';
        }

        var yesterday = new Date(today);
        yesterday.setDate(yesterday.getDate() - 1);
        if (msgDay.getTime() === yesterday.getTime()) {
            return 'Tegnap';
        }

        return d.getFullYear() + '. ' +
            pad(d.getMonth() + 1) + '. ' +
            pad(d.getDate()) + '.';
    }

    function formatTime(dateStr) {
        if (!dateStr) return '';
        var d = new Date(dateStr);
        return pad(d.getHours()) + ':' + pad(d.getMinutes());
    }

    function pad(n) {
        return n < 10 ? '0' + n : '' + n;
    }

    // ==========================================
    // Inicializalas DOM betolteskor
    // ==========================================
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // ==========================================
    // Takaritas oldal elhagyaskor
    // ==========================================
    window.addEventListener('beforeunload', stopPolling);

    // Publikus API
    return {
        switchConversation: switchConversation,
        handleSend: handleSend,
        deleteMessage: deleteMessage,
        showContextMenu: showContextMenu
    };
})();
