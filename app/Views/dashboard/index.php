<?php
use App\Core\Auth;

$isOwner = Auth::isOwner();
$currentUser = Auth::user();
$hour = (int)date('H');
$greeting = $hour < 12 ? 'Jó reggelt' : ($hour < 18 ? 'Jó napot' : 'Jó estét');
?>

<style>
.dash-card { background: rgba(255,255,255,0.85); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.6); border-radius: 1rem; }
.dash-fade { opacity:0; transform:translateY(10px); animation:dashIn 0.4s ease forwards; }
@keyframes dashIn { to { opacity:1; transform:translateY(0); } }
.gauge-ring { transition: stroke-dashoffset 1s cubic-bezier(0.4,0,0.2,1); }
@keyframes gaugeReveal { from { stroke-dashoffset: <?= 2 * M_PI * 22 ?>; } }
.qa { background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.06); transition:all 0.2s; }
.qa:hover { background:rgba(217,255,84,0.1); border-color:rgba(217,255,84,0.25); }
</style>

<div class="flex gap-5 lg:h-[calc(100vh-6.5rem)]">
    <!-- BAL -->
    <div class="flex-1 min-w-0 flex flex-col gap-3 sm:gap-4">

        <!-- Banner -->
        <div class="dash-fade flex-shrink-0 relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#0b0f0e] via-[#1a1f1e] to-[#0b0f0e] px-3 sm:px-6 py-3 sm:py-5" style="animation-delay:0s">
            <div class="absolute top-0 right-0 w-56 h-56 bg-accent/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/4"></div>
            <div class="relative z-10 flex items-center justify-between">
                <div>
                    <p class="text-accent/60 text-[11px] font-bold uppercase tracking-[0.15em] mb-0.5"><?= date('Y. F j.') ?> · <?= ['Vasárnap','Hétfő','Kedd','Szerda','Csütörtök','Péntek','Szombat'][date('w')] ?></p>
                    <h1 class="text-white text-lg sm:text-2xl font-heading font-extrabold tracking-tight"><?= $greeting ?>, <?= e($currentUser['name']) ?>!</h1>
                </div>
                <div class="hidden sm:flex gap-2">
                    <a href="<?= base_url('/finance/create') ?>" class="qa px-4 py-2.5 rounded-xl text-gray-300 hover:text-accent text-xs font-medium flex items-center gap-1.5"><i class="fa-solid fa-circle-plus"></i> Pénzmozgás</a>
                    <a href="<?= base_url('/evaluations/create') ?>" class="qa px-4 py-2.5 rounded-xl text-gray-300 hover:text-accent text-xs font-medium flex items-center gap-1.5"><i class="fa-solid fa-star"></i> Értékelés</a>
                    <a href="<?= base_url('/schedule') ?>" class="qa px-4 py-2.5 rounded-xl text-gray-300 hover:text-accent text-xs font-medium flex items-center gap-1.5"><i class="fa-solid fa-calendar-days"></i> Beosztás</a>
                </div>
            </div>
        </div>

        <?php if ($isOwner): ?>
        <!-- Kassza + Forgalom + Értékelések EGY sorban, egyforma magas -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 flex-shrink-0 dash-fade" style="animation-delay:0.1s">
            <?php if (!empty($data['kasszaByStore'])): ?>
            <div class="dash-card p-5 flex flex-col justify-between">
                <p class="text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-3"><i class="fa-solid fa-vault text-amber-500 mr-1"></i>Kassza egyenleg</p>
                <div class="space-y-2.5 flex-1 flex flex-col justify-center">
                    <?php foreach ($data['kasszaByStore'] as $store): ?>
                    <div class="flex items-center justify-between px-3 py-2 rounded-lg <?= $store['kassza_egyenleg'] >= 0 ? 'bg-emerald-50/50' : 'bg-red-50/50' ?>">
                        <span class="text-sm text-gray-600 font-medium"><?= e($store['name']) ?></span>
                        <span class="text-base font-extrabold font-heading <?= $store['kassza_egyenleg'] >= 0 ? 'text-emerald-700' : 'text-red-600' ?>"><?= format_money($store['kassza_egyenleg']) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Kassza eltérés figyelmeztetés -->
                <?php if (!empty($data['kasszaAlerts'])): ?>
                <div class="mt-3 pt-3 border-t border-red-200 space-y-1.5">
                    <?php foreach ($data['kasszaAlerts'] as $alert): ?>
                    <div class="flex items-start gap-2 px-3 py-2 bg-red-50 rounded-lg border border-red-200">
                        <i class="fa-solid fa-triangle-exclamation text-red-500 text-xs mt-0.5"></i>
                        <div class="text-[11px]">
                            <p class="font-bold text-red-700"><?= e($alert['store_name']) ?> — eltérés!</p>
                            <p class="text-red-600">
                                Számított: <?= format_money($alert['expected']) ?> →
                                Nyitó: <?= format_money($alert['actual']) ?>
                                <span class="font-bold">(<?= $alert['diff'] > 0 ? '+' : '' ?><?= format_money($alert['diff']) ?>)</span>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($data['monthlyByStore'])): ?>
            <div class="dash-card p-5 flex flex-col">
                <p class="text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-3"><i class="fa-solid fa-chart-column text-blue-500 mr-1"></i>Havi forgalom</p>
                <div class="flex-1 flex items-center">
                    <canvas id="revenueChart" class="w-full"></canvas>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($data['employeeEvals'])): ?>
            <div class="dash-card p-5 flex flex-col">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-bold text-on-surface-variant uppercase tracking-widest"><i class="fa-solid fa-trophy text-yellow-500 mr-1"></i>Értékelések</p>
                    <span class="text-[9px] text-gray-400 font-medium"><?= date('F') ?> · 90%+=prémium</span>
                </div>
                <div class="grid grid-cols-3 gap-x-2 gap-y-3 flex-1 content-center">
                    <?php foreach ($data['employeeEvals'] as $i => $emp): ?>
                    <?php
                        $ratio = $emp['total_customers'] > 0 ? round(($emp['total_reviews'] / $emp['total_customers']) * 100, 1) : 0;
                        $isPremium = $ratio >= 90;
                        $ringColor = $ratio >= 90 ? '#22c55e' : ($ratio >= 70 ? '#eab308' : '#ef4444');
                        $circ = 2 * M_PI * 22;
                        $off = $circ - ($circ * min($ratio, 100) / 100);
                    ?>
                    <div class="flex flex-col items-center gap-1" title="<?= e($emp['name']) ?>: <?= $ratio ?>%">
                        <div class="relative w-12 h-12">
                            <svg viewBox="0 0 52 52" class="w-full h-full -rotate-90">
                                <circle cx="26" cy="26" r="22" fill="none" stroke="#f3f4f6" stroke-width="4"/>
                                <circle cx="26" cy="26" r="22" fill="none" stroke="<?= $ringColor ?>" stroke-width="4" stroke-linecap="round" class="gauge-ring" stroke-dasharray="<?= $circ ?>" stroke-dashoffset="<?= $off ?>" style="animation:gaugeReveal 1s ease forwards;animation-delay:<?= 0.3+($i*0.08) ?>s;stroke-dashoffset:<?= $circ ?>"/>
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <?php if ($isPremium): ?><i class="fa-solid fa-trophy text-yellow-500 text-xs"></i>
                                <?php else: ?><span class="text-[10px] font-extrabold" style="color:<?= $ringColor ?>"><?= round($ratio) ?>%</span><?php endif; ?>
                            </div>
                        </div>
                        <span class="text-[9px] font-semibold text-gray-500 text-center leading-tight max-w-[65px] truncate"><?= e($emp['name']) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <?php else: ?>
        <!-- BOLT FIÓK: Értékelések -->
        <?php if (!empty($data['employeeEvals'])): ?>
        <div class="dash-card p-5 flex-shrink-0 dash-fade" style="animation-delay:0.1s">
            <p class="text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-3"><i class="fa-solid fa-trophy text-yellow-500 mr-1"></i>Értékelések — <?= date('F') ?></p>
            <div class="flex flex-wrap gap-5 justify-center">
                <?php foreach ($data['employeeEvals'] as $i => $emp): ?>
                <?php
                    $ratio = $emp['total_customers'] > 0 ? round(($emp['total_reviews'] / $emp['total_customers']) * 100, 1) : 0;
                    $isPremium = $ratio >= 90;
                    $ringColor = $ratio >= 90 ? '#22c55e' : ($ratio >= 70 ? '#eab308' : '#ef4444');
                    $circ = 2 * M_PI * 22; $off = $circ - ($circ * min($ratio, 100) / 100);
                ?>
                <div class="flex flex-col items-center gap-1">
                    <div class="relative w-14 h-14">
                        <svg viewBox="0 0 52 52" class="w-full h-full -rotate-90">
                            <circle cx="26" cy="26" r="22" fill="none" stroke="#f3f4f6" stroke-width="4"/>
                            <circle cx="26" cy="26" r="22" fill="none" stroke="<?= $ringColor ?>" stroke-width="4" stroke-linecap="round" class="gauge-ring" stroke-dasharray="<?= $circ ?>" stroke-dashoffset="<?= $off ?>" style="animation:gaugeReveal 1s ease forwards;animation-delay:<?= 0.2+($i*0.08) ?>s;stroke-dashoffset:<?= $circ ?>"/>
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <?php if ($isPremium): ?><i class="fa-solid fa-trophy text-yellow-500 text-sm"></i>
                            <?php else: ?><span class="text-[11px] font-extrabold" style="color:<?= $ringColor ?>"><?= round($ratio) ?>%</span><?php endif; ?>
                        </div>
                    </div>
                    <span class="text-[10px] font-semibold text-gray-500 text-center max-w-[70px] truncate"><?= e($emp['name']) ?></span>
                    <?php if ($isPremium): ?><span class="text-[7px] font-extrabold text-emerald-600 bg-emerald-100 px-1.5 py-0.5 rounded-full uppercase">Prémium</span><?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <!-- Bérpapír értesítés (bolt fiókoknak) -->
        <?php if (!$isOwner && !empty($data['newPayslips'])): ?>
        <div class="dash-fade flex-shrink-0 bg-purple-50 border border-purple-200 rounded-xl p-4" style="animation-delay:0.2s">
            <div class="flex items-center gap-2 mb-2">
                <i class="fa-solid fa-file-contract text-purple-600"></i>
                <p class="text-sm font-bold text-purple-800">Új bérpapírok — nyomtasd ki és írasd alá!</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($data['newPayslips'] as $ps): ?>
                <a href="<?= base_url($ps['file_path']) ?>" target="_blank"
                   class="flex items-center gap-2 px-3 py-2 bg-white rounded-lg border border-purple-200 hover:border-purple-400 hover:bg-purple-50 transition-colors">
                    <i class="fa-solid fa-download text-purple-500 text-xs"></i>
                    <span class="text-sm font-medium text-purple-800"><?= e($ps['employee_name']) ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Feladatok grid -->
        <div class="dash-fade flex-shrink-0" style="animation-delay:0.25s" id="daily-tasks-card">
            <div class="flex items-center gap-2 mb-2">
                <p class="text-xs font-bold text-on-surface-variant uppercase tracking-widest"><?= $isOwner ? 'Elmaradt feladatok' : 'Napi feladatok' ?></p>
                <span id="daily-tasks-count" class="text-xs font-bold"></span>
            </div>
            <div id="daily-tasks-list" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2" data-owner="<?= $isOwner ? '1' : '0' ?>">
                <div class="text-xs text-gray-400 text-center py-2 col-span-full">Betöltés...</div>
            </div>
        </div>

    </div>

    <!-- JOBB: Chat (desktop) -->
    <div class="w-[26rem] flex-shrink-0 hidden lg:flex flex-col rounded-2xl overflow-hidden border border-surface-container dash-fade"
         style="animation-delay:0.1s;background:rgba(255,255,255,0.85);backdrop-filter:blur(20px);"
         id="dashboard-chat" data-user-id="<?= Auth::id() ?>" data-base-url="<?= base_url('') ?>">

        <div class="px-4 py-3 bg-gradient-to-r from-[#0b0f0e] to-[#1a1f1e] text-white flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-comments text-accent text-sm"></i>
                <span class="font-heading font-bold text-sm">Közös chat</span>
            </div>
            <div class="flex items-center gap-1 bg-emerald-500/15 px-2 py-0.5 rounded-full">
                <span class="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-pulse"></span>
                <span class="text-[9px] text-emerald-400 font-bold uppercase tracking-wider">Online</span>
            </div>
        </div>

        <div id="chat-messages" class="flex-1 overflow-y-auto p-4 space-y-2.5" style="min-height:0;">
            <div class="text-center text-xs text-gray-400 py-6"><i class="fa-solid fa-comments text-xl mb-1 block text-gray-300"></i>Betöltés...</div>
        </div>

        <div id="chat-image-preview" class="hidden px-3 pt-2 flex items-center gap-2">
            <img id="chat-preview-img" class="w-16 h-16 object-cover rounded-lg border">
            <button type="button" onclick="clearChatImage('desktop')" class="text-red-500 text-xs"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="p-3 border-t border-surface-container/80 bg-surface-container-low/50 flex-shrink-0">
            <form id="chat-form" class="flex gap-2" onsubmit="return sendDashboardChat(event)" enctype="multipart/form-data">
                <input type="file" id="chat-image-input" accept="image/*" capture="environment" class="hidden" onchange="previewChatImage(this, 'desktop')">
                <button type="button" onclick="document.getElementById('chat-image-input').click()" class="px-3 py-2.5 text-gray-400 hover:text-accent rounded-xl transition-colors"><i class="fa-solid fa-camera"></i></button>
                <input type="text" id="chat-input" class="flex-1 px-4 py-2.5 bg-white border border-surface-container rounded-xl text-sm focus:ring-2 focus:ring-accent/30 focus:border-accent" placeholder="Üzenet írása..." autocomplete="off">
                <button type="submit" class="px-4 py-2.5 bg-sidebar text-accent rounded-xl hover:bg-gray-800 transition-all active:scale-95"><i class="fa-solid fa-paper-plane"></i></button>
            </form>
        </div>
    </div>
</div>

<!-- MOBIL: Chat panel alul -->
<div class="lg:hidden fixed bottom-0 left-0 right-0 z-40 transition-transform duration-300" id="mobile-chat-wrapper"
     data-user-id="<?= Auth::id() ?>" data-base-url="<?= base_url('') ?>">

    <!-- Összecsukott sáv -->
    <div id="mobile-chat-bar" class="bg-gradient-to-r from-[#0b0f0e] to-[#1a1f1e] text-white px-4 py-2.5 flex items-center justify-between cursor-pointer shadow-lg"
         onclick="toggleMobileChat()">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-comments text-accent text-sm"></i>
            <span class="font-heading font-bold text-xs">Közös chat</span>
            <span id="mobile-chat-badge" class="hidden min-w-[18px] h-[18px] bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center px-1">0</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-pulse"></span>
            <i class="fa-solid fa-chevron-up text-gray-400 text-xs transition-transform" id="mobile-chat-arrow"></i>
        </div>
    </div>

    <!-- Kinyitott chat -->
    <div id="mobile-chat-panel" class="hidden bg-white border-t border-gray-200 flex flex-col" style="height: 55vh;">
        <!-- Üzenetek (flex-1 = maradék helyet foglalja) -->
        <div id="mobile-chat-messages" class="flex-1 overflow-y-auto p-3 space-y-2 min-h-0">
            <div class="text-center text-xs text-gray-400 py-6">Betöltés...</div>
        </div>

        <!-- Kép előnézet (ha van kiválasztott kép) -->
        <div id="mobile-chat-image-preview" class="hidden px-3 py-2 bg-gray-50 border-t border-gray-100 flex items-center gap-3 flex-shrink-0">
            <img id="mobile-preview-img" class="w-14 h-14 object-cover rounded-lg border border-gray-300">
            <span class="text-xs text-gray-500 flex-1">Kép csatolva</span>
            <button type="button" onclick="clearChatImage('mobile')" class="w-7 h-7 flex items-center justify-center bg-red-100 text-red-500 rounded-full text-xs"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <!-- Input sor (mindig alul, flex-shrink-0) -->
        <div class="p-2 border-t border-gray-100 bg-gray-50 flex-shrink-0">
            <form id="mobile-chat-form" class="flex items-center gap-1" onsubmit="return sendMobileChat(event)" enctype="multipart/form-data">
                <input type="file" id="mobile-chat-image-input" accept="image/*" capture="environment" class="hidden" onchange="previewChatImage(this, 'mobile')">
                <button type="button" onclick="document.getElementById('mobile-chat-image-input').click()" class="w-9 h-9 flex-shrink-0 flex items-center justify-center text-gray-400 hover:text-primary rounded-full"><i class="fa-solid fa-camera text-sm"></i></button>
                <input type="text" id="mobile-chat-input" class="flex-1 min-w-0 px-3 py-2 bg-white border border-gray-200 rounded-xl text-sm" placeholder="Üzenet..." autocomplete="off">
                <button type="submit" class="w-9 h-9 flex-shrink-0 flex items-center justify-center bg-sidebar text-accent rounded-full"><i class="fa-solid fa-paper-plane text-sm"></i></button>
            </form>
        </div>
    </div>
</div>

<script>
let mobileChatOpen = false;
let chatImageFile = { desktop: null, mobile: null };

function previewChatImage(input, type) {
    if (input.files && input.files[0]) {
        chatImageFile[type] = input.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewId = type === 'desktop' ? 'chat-preview-img' : 'mobile-preview-img';
            const wrapperId = type === 'desktop' ? 'chat-image-preview' : 'mobile-chat-image-preview';
            document.getElementById(previewId).src = e.target.result;
            document.getElementById(wrapperId).classList.remove('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function clearChatImage(type) {
    chatImageFile[type] = null;
    const inputId = type === 'desktop' ? 'chat-image-input' : 'mobile-chat-image-input';
    const wrapperId = type === 'desktop' ? 'chat-image-preview' : 'mobile-chat-image-preview';
    document.getElementById(inputId).value = '';
    document.getElementById(wrapperId).classList.add('hidden');
}

function openChatImage(src) {
    const overlay = document.createElement('div');
    overlay.className = 'fixed inset-0 z-[100] bg-black/90 flex items-center justify-center p-4';
    overlay.onclick = () => overlay.remove();
    overlay.innerHTML = '<img src="' + src + '" class="max-w-full max-h-full rounded-xl">' +
        '<button class="absolute top-4 right-4 w-10 h-10 bg-white/20 text-white rounded-full text-xl flex items-center justify-center" onclick="this.parentElement.remove()"><i class="fa-solid fa-xmark"></i></button>';
    document.body.appendChild(overlay);
}

function renderChatMessage(m, userId, baseUrl) {
    const isMine = parseInt(m.sender_id) === userId;
    const initials = (m.sender_name || '??').substring(0, 2);
    const align = isMine ? 'justify-end' : 'justify-start';
    const avatarBg = isMine ? 'bg-accent text-sidebar' : 'bg-gray-300 text-gray-600';

    let content = '';
    if (m.image_path) {
        content += '<img src="' + baseUrl + m.image_path + '" class="max-w-[180px] rounded-lg cursor-pointer" onclick="openChatImage(this.src)" loading="lazy">';
    }
    if (m.message) {
        content += '<span class="text-xs">' + escapeHtml(m.message) + '</span>';
    }

    // Ha csak kép van, ne legyen sötét háttér
    const hasText = m.message && m.message.trim();
    const bg = m.image_path && !hasText
        ? (isMine ? 'bg-transparent' : 'bg-transparent')
        : (isMine ? 'bg-sidebar text-accent' : 'bg-gray-100 text-gray-800');
    const padding = m.image_path && !hasText ? 'p-0.5' : 'px-3 py-1.5';

    let html = '<div class="flex ' + align + ' gap-1.5">';
    if (!isMine) html += '<div class="w-6 h-6 rounded-full ' + avatarBg + ' flex items-center justify-center text-[8px] font-bold flex-shrink-0">' + initials + '</div>';
    html += '<div class="max-w-[75%] ' + padding + ' rounded-xl ' + bg + '">' + content + '</div>';
    if (isMine) html += '<div class="w-6 h-6 rounded-full ' + avatarBg + ' flex items-center justify-center text-[8px] font-bold flex-shrink-0">' + initials + '</div>';
    html += '</div>';
    return html;
}

function toggleMobileChat() {
    mobileChatOpen = !mobileChatOpen;
    const panel = document.getElementById('mobile-chat-panel');
    const arrow = document.getElementById('mobile-chat-arrow');
    panel.classList.toggle('hidden', !mobileChatOpen);
    arrow.style.transform = mobileChatOpen ? 'rotate(180deg)' : '';
    if (mobileChatOpen) loadMobileChat();
}

let mobileLastMsgId = 0;
async function loadMobileChat() {
    const el = document.getElementById('mobile-chat-wrapper');
    const baseUrl = el.dataset.baseUrl;
    try {
        const res = await fetch(baseUrl + '/chat/messages?type=public');
        const data = await res.json();
        const msgs = data.messages || data || [];
        const userId = parseInt(el.dataset.userId);
        const container = document.getElementById('mobile-chat-messages');

        if (msgs.length === 0) {
            container.innerHTML = '<div class="text-center text-xs text-gray-400 py-6">Nincs üzenet.</div>';
            return;
        }

        // Csak frissítünk ha van új üzenet (ne villogjon)
        const newLastId = Math.max(...msgs.map(m => m.id));
        if (newLastId === mobileLastMsgId) return;
        mobileLastMsgId = newLastId;

        let html = '';
        msgs.forEach(m => { html += renderChatMessage(m, userId, baseUrl); });
        container.innerHTML = html;
        container.scrollTop = container.scrollHeight;
    } catch(e) {}
}

async function sendMobileChat(e) {
    e.preventDefault();
    const input = document.getElementById('mobile-chat-input');
    const msg = input.value.trim();
    const imgFile = chatImageFile.mobile;
    if (!msg && !imgFile) return false;

    const el = document.getElementById('mobile-chat-wrapper');
    const baseUrl = el.dataset.baseUrl;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    const fd = new FormData();
    fd.append('_csrf', csrf);
    fd.append('message', msg);
    if (imgFile) fd.append('chat_image', imgFile);

    try {
        await fetch(baseUrl + '/chat/send', {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': csrf},
            body: fd
        });
        input.value = '';
        clearChatImage('mobile');
        loadMobileChat();
    } catch(e) {}
    return false;
}

function escapeHtml(s) { const d = document.createElement('div'); d.textContent = s||''; return d.innerHTML; }

// Periodikus frissítés
setInterval(() => { if (mobileChatOpen) loadMobileChat(); }, 500);
</script>

<!-- Chart.js -->
<?php if ($isOwner && !empty($data['monthlyByStore'])): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('revenueChart');
    if (!ctx) return;

    const labels = <?= json_encode(array_column($data['monthlyByStore'], 'name'), JSON_UNESCAPED_UNICODE) ?>;
    const values = <?= json_encode(array_map(fn($s) => (float)$s['total'], $data['monthlyByStore'])) ?>;
    const colors = ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899'];

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Forgalom (Ft)',
                data: values,
                backgroundColor: labels.map((_, i) => colors[i % colors.length] + '30'),
                borderColor: labels.map((_, i) => colors[i % colors.length]),
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            return new Intl.NumberFormat('hu-HU').format(ctx.raw) + ' Ft';
                        }
                    },
                    backgroundColor: '#0b0f0e',
                    titleFont: { family: 'Manrope', weight: 'bold' },
                    bodyFont: { family: 'Inter' },
                    cornerRadius: 10,
                    padding: 12,
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f3f4f6' },
                    border: { display: false },
                    ticks: {
                        font: { family: 'Inter', size: 10 },
                        color: '#9ca3af',
                        callback: function(v) {
                            if (v >= 1000000) return (v/1000000).toFixed(1) + 'M';
                            if (v >= 1000) return (v/1000).toFixed(0) + 'k';
                            return v;
                        }
                    }
                },
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: { font: { family: 'Manrope', size: 12, weight: 'bold' }, color: '#374151' }
                }
            },
            animation: { duration: 800, easing: 'easeOutQuart' }
        }
    });
});
</script>
<?php endif; ?>

<!-- Feladat JS -->
<script>
(function() {
    const baseUrl = '<?= base_url('') ?>';
    async function loadDailyTasks() {
        try {
            const res = await fetch(baseUrl + '/tasks/api'), data = await res.json();
            const list = document.getElementById('daily-tasks-list'), countEl = document.getElementById('daily-tasks-count'), card = document.getElementById('daily-tasks-card');
            if (!list) return;
            const isO = list.dataset.owner === '1';
            let tasks = data.tasks || [];
            if (isO) tasks = tasks.filter(t => t.overdue && !t.done);
            const p = tasks.filter(t => !t.done).length;
            if (!tasks.length) { if (isO) card.classList.add('hidden'); else { countEl.textContent='✓'; countEl.className='text-xs font-bold text-emerald-600'; list.innerHTML='<div class="col-span-full text-center text-xs text-emerald-500 py-1"><i class="fa-solid fa-circle-check mr-1"></i>Minden kész!</div>'; } return; }
            card.classList.remove('hidden');
            countEl.textContent = p > 0 ? '('+p+')' : '✓';
            countEl.className = 'text-xs font-bold ' + (p > 0 ? 'text-red-500' : 'text-emerald-600');
            let h = '';
            tasks.forEach(t => {
                if (t.done && !isO) h += '<div class="rounded-xl p-3 bg-emerald-50/60 border border-emerald-200/30 text-center"><i class="fa-solid fa-circle-check text-emerald-400 text-base block mb-0.5"></i><p class="text-[10px] text-emerald-500/60 line-through leading-tight truncate">'+esc(t.text)+'</p></div>';
                else if (t.overdue && !t.done) h += '<a href="'+baseUrl+t.link+'" class="rounded-xl p-3 bg-red-50 border border-red-200 text-center hover:bg-red-100/70 transition-all block relative"><div class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-red-500 rounded-full animate-pulse"></div><i class="fa-solid '+t.icon+' text-red-400 text-base block mb-0.5"></i><p class="text-[10px] text-red-700 font-bold leading-tight truncate">'+esc(t.text)+'</p></a>';
                else if (!t.done) h += '<a href="'+baseUrl+t.link+'" class="rounded-xl p-3 bg-white border border-gray-200/60 text-center hover:border-[#d4fa4f] hover:bg-[#d4fa4f]/10 transition-all block group"><i class="fa-solid '+t.icon+' text-gray-300 group-hover:text-[#506300] text-base block mb-0.5 transition-colors"></i><p class="text-[10px] text-gray-500 font-medium leading-tight truncate">'+esc(t.text)+'</p></a>';
            });
            list.innerHTML = h;
        } catch(e) {}
    }
    function esc(s) { const d=document.createElement('div'); d.textContent=s||''; return d.innerHTML; }
    loadDailyTasks(); setInterval(loadDailyTasks, 60000);
})();
</script>

<!-- Chat JS -->
<script>
(function() {
    const el = document.getElementById('dashboard-chat'); if(!el) return;
    const base=el.dataset.baseUrl, uid=parseInt(el.dataset.userId), md=document.getElementById('chat-messages');
    let lid=0, first=true;
    function beep(){try{const c=new(window.AudioContext||window.webkitAudioContext)(),o=c.createOscillator(),g=c.createGain();o.connect(g);g.connect(c.destination);o.frequency.value=830;o.type='sine';g.gain.setValueAtTime(0.25,c.currentTime);g.gain.exponentialRampToValueAtTime(0.01,c.currentTime+0.25);o.start(c.currentTime);o.stop(c.currentTime+0.25);const o2=c.createOscillator(),g2=c.createGain();o2.connect(g2);g2.connect(c.destination);o2.frequency.value=1250;o2.type='sine';g2.gain.setValueAtTime(0.18,c.currentTime+0.12);g2.gain.exponentialRampToValueAtTime(0.01,c.currentTime+0.35);o2.start(c.currentTime+0.12);o2.stop(c.currentTime+0.35);}catch(e){}}
    function esc(s){const d=document.createElement('div');d.textContent=s;return d.innerHTML;}
    async function load(){
        try{const r=await fetch(base+'/chat/messages?type=public&limit=50');if(!r.ok)return;const json=await r.json();
        const msgs=json.messages||json||[];
        if(!msgs.length){md.innerHTML='<div class="text-center text-xs text-gray-400 py-4"><i class="fa-solid fa-comments text-lg mb-1 block text-gray-300"></i>Még nincsenek üzenetek.</div>';first=false;return;}
        const nid=Math.max(...msgs.map(m=>m.id));if(nid===lid)return;
        if(!first&&lid>0){const nm=msgs.find(m=>m.id===nid);if(nm&&nm.sender_id!=uid)beep();}first=false;lid=nid;
        let sorted=[...msgs];sorted.sort((a,b)=>a.id-b.id);let h='',ld='';
        sorted.forEach(m=>{const dt=new Date(m.created_at),t=dt.toLocaleTimeString('hu-HU',{hour:'2-digit',minute:'2-digit'}),ds=dt.toLocaleDateString('hu-HU');
        if(ds!==ld){h+='<div class="text-center my-1"><span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider bg-surface-container/60 px-2 py-0.5 rounded-full">'+ds+'</span></div>';ld=ds;}
        const name = m.sender_name||'?';
        const mono = esc(name.substring(0,2));
        let msgContent = '';
        const hasImg = !!m.image_path;
        const hasText = m.message && m.message.trim();
        if(hasImg) msgContent += '<img src="'+base+m.image_path+'" class="max-w-[200px] rounded-lg cursor-pointer mb-1" onclick="openChatImage(this.src)" loading="lazy">';
        if(hasText) msgContent += esc(m.message);
        const bubbleBg = hasImg && !hasText ? 'p-0.5' : 'px-3.5 py-2';
        if(m.sender_id==uid)h+='<div class="flex justify-end gap-2"><div class="max-w-[75%]"><div class="'+(hasImg&&!hasText?'':'bg-sidebar text-accent')+' '+bubbleBg+' rounded-2xl rounded-br-md text-sm">'+msgContent+'</div><p class="text-[9px] text-gray-400 mt-0.5 text-right">'+t+'</p></div><div class="w-7 h-7 rounded-full bg-sidebar text-accent flex items-center justify-center text-[9px] font-bold flex-shrink-0 mt-0.5">'+mono+'</div></div>';
        else h+='<div class="flex justify-start gap-2"><div class="w-7 h-7 rounded-full bg-surface-container-high text-on-surface-variant flex items-center justify-center text-[9px] font-bold flex-shrink-0 mt-0.5">'+mono+'</div><div class="max-w-[75%]"><p class="text-[9px] font-bold text-gray-400 mb-0.5">'+esc(name)+'</p><div class="'+(hasImg&&!hasText?'':'bg-surface-container-high/80')+' '+bubbleBg+' rounded-2xl rounded-bl-md text-sm text-on-surface">'+msgContent+'</div><p class="text-[9px] text-gray-400 mt-0.5">'+t+'</p></div></div>';});
        md.innerHTML=h;md.scrollTop=md.scrollHeight;}catch(e){}}
    window.sendDashboardChat=async function(e){e.preventDefault();const i=document.getElementById('chat-input'),m=i.value.trim();const imgFile=chatImageFile.desktop;if(!m&&!imgFile)return false;i.value='';
    const fd=new FormData();fd.append('_csrf',getCsrfToken());fd.append('message',m);if(imgFile)fd.append('chat_image',imgFile);
    try{await fetch(base+'/chat/send',{method:'POST',headers:{'X-CSRF-TOKEN':getCsrfToken()},body:fd});clearChatImage('desktop');lid=0;await load();}catch(e){}return false;};
    load();setInterval(load,500);
})();
</script>
