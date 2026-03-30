<?php
use App\Core\Auth;
use App\Core\Database;

$currentUser = Auth::user();
$isOwner = Auth::isOwner();

// Gyors statisztikák a navbarba
$db = Database::getInstance();
if ($isOwner) {
    $_navStoreCount = (int)$db->query('SELECT COUNT(*) FROM stores')->fetchColumn();
    $_navEmpCount = (int)$db->query("SELECT COUNT(*) FROM employees WHERE is_active = 1")->fetchColumn();
    $_navTodayStmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM financial_records WHERE record_date = CURDATE() AND purpose IN ('napi_keszpenz','napi_bankkartya')");
    $_navTodayStmt->execute();
    $_navTodayRevenue = (float)$_navTodayStmt->fetchColumn();

    // Mai dolgozók boltonként
    $_navWorkers = $db->query(
        "SELECT s.name as store_name, GROUP_CONCAT(e.name ORDER BY e.name SEPARATOR ', ') as workers
         FROM schedules sc
         JOIN stores s ON sc.store_id = s.id
         JOIN employees e ON sc.employee_id = e.id
         WHERE sc.work_date = CURDATE()
         GROUP BY s.id, s.name ORDER BY s.name"
    )->fetchAll();
} else {
    $storeId = Auth::storeId();
    $_navStoreCount = null;
    $_navEmpCount = null;
    $_navTodayStmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM financial_records WHERE store_id = :s AND record_date = CURDATE() AND purpose IN ('napi_keszpenz','napi_bankkartya')");
    $_navTodayStmt->execute(['s' => $storeId]);
    $_navTodayRevenue = (float)$_navTodayStmt->fetchColumn();
    // Kassza egyenleg = induló + bevételek - kiadások
    $_navOpenCash = $db->prepare("SELECT COALESCE(opening_cash, 0) FROM stores WHERE id = :s");
    $_navOpenCash->execute(['s' => $storeId]);
    $_navOpenCash = (float)$_navOpenCash->fetchColumn();

    $_navKasszaStmt = $db->prepare("SELECT
        COALESCE(SUM(CASE WHEN purpose IN ('befizetes_bankbol','befizetes_boltbol','napi_keszpenz','selejt_befizetes') THEN amount ELSE 0 END),0)
        - COALESCE(SUM(CASE WHEN purpose IN ('meretre_igazitas','tankolas','munkaber','egyeb_kifizetes','szamla_kifizetes','bank_kifizetes') THEN amount ELSE 0 END),0)
        FROM financial_records WHERE store_id = :s");
    $_navKasszaStmt->execute(['s' => $storeId]);
    $_navKassza = $_navOpenCash + (float)$_navKasszaStmt->fetchColumn();

    // Mai dolgozók (saját bolt)
    $stmt = $db->prepare(
        "SELECT GROUP_CONCAT(e.name ORDER BY e.name SEPARATOR ', ') as workers
         FROM schedules sc JOIN employees e ON sc.employee_id = e.id
         WHERE sc.store_id = :s AND sc.work_date = CURDATE()"
    );
    $stmt->execute(['s' => $storeId]);
    $_navMyWorkers = $stmt->fetchColumn() ?: null;
}
?>
<header class="fixed top-0 right-0 w-full lg:w-[calc(100%-16rem)] z-20 bg-surface/80 backdrop-blur-xl border-b border-gray-200/50">
    <div class="flex items-center justify-between px-2 sm:px-4 md:px-6 py-2">
        <!-- Left: Hamburger + Bolt név + Oldal címe -->
        <div class="flex items-center gap-3">
            <button onclick="toggleSidebar()" class="lg:hidden p-2.5 text-gray-600 hover:bg-gray-200/50 rounded-xl transition-colors">
                <i class="fa-solid fa-bars"></i>
            </button>

            <?php if (!$isOwner && !empty($currentUser['store_name'])): ?>
            <div class="flex items-center gap-2 bg-primary-container/30 px-3 py-1.5 rounded-full">
                <i class="fa-solid fa-store text-xs text-on-primary-container"></i>
                <span class="text-xs font-bold text-on-primary-container"><?= e($currentUser['store_name']) ?></span>
            </div>
            <div class="h-5 w-px bg-gray-300 hidden sm:block"></div>
            <?php endif; ?>

            <h2 class="font-heading font-bold text-sm sm:text-base text-gray-900 truncate max-w-[120px] sm:max-w-none"><?= e($data['pageTitle'] ?? '') ?></h2>
        </div>

        <!-- Center: Statisztikák -->
        <div class="hidden md:flex items-center gap-1">
            <?php if ($isOwner && $_navStoreCount !== null): ?>
            <div class="flex items-center gap-1.5 px-3 py-1.5 bg-surface-container-low rounded-full">
                <i class="fa-solid fa-store text-[10px] text-primary"></i>
                <span class="text-[11px] font-bold text-on-surface"><?= $_navStoreCount ?></span>
                <span class="text-[10px] text-on-surface-variant">bolt</span>
            </div>
            <div class="flex items-center gap-1.5 px-3 py-1.5 bg-surface-container-low rounded-full">
                <i class="fa-solid fa-users text-[10px] text-blue-500"></i>
                <span class="text-[11px] font-bold text-on-surface"><?= $_navEmpCount ?></span>
                <span class="text-[10px] text-on-surface-variant">dolgozó</span>
            </div>
            <?php endif; ?>
            <div class="flex items-center gap-1.5 px-3 py-1.5 bg-surface-container-low rounded-full">
                <i class="fa-solid fa-arrow-trend-up text-[10px] text-emerald-500"></i>
                <span class="text-[11px] font-bold text-on-surface"><?= format_money($_navTodayRevenue) ?></span>
                <span class="text-[10px] text-on-surface-variant">ma</span>
            </div>
            <?php if (!$isOwner && isset($_navKassza)): ?>
            <div class="flex items-center gap-1.5 px-3 py-1.5 bg-surface-container-low rounded-full">
                <i class="fa-solid fa-vault text-[10px] text-amber-500"></i>
                <span class="text-[11px] font-bold <?= $_navKassza >= 0 ? 'text-emerald-700' : 'text-red-600' ?>"><?= format_money($_navKassza) ?></span>
                <span class="text-[10px] text-on-surface-variant">kassza</span>
            </div>
            <?php endif; ?>

            <!-- Mai dolgozók -->
            <div class="h-4 w-px bg-gray-300 mx-1"></div>
            <?php if ($isOwner && !empty($_navWorkers)): ?>
                <?php foreach ($_navWorkers as $sw): ?>
                <div class="flex items-center gap-1.5 px-2.5 py-1.5 bg-blue-50 rounded-full">
                    <i class="fa-solid fa-user-clock text-[9px] text-blue-500"></i>
                    <span class="text-[10px] font-bold text-blue-700"><?= e($sw['store_name']) ?>:</span>
                    <span class="text-[10px] text-blue-600"><?= e($sw['workers']) ?></span>
                </div>
                <?php endforeach; ?>
            <?php elseif ($isOwner): ?>
                <div class="flex items-center gap-1.5 px-2.5 py-1.5 bg-gray-100 rounded-full">
                    <i class="fa-solid fa-user-clock text-[9px] text-gray-400"></i>
                    <span class="text-[10px] text-gray-400">Nincs beosztás mára</span>
                </div>
            <?php elseif (!$isOwner && !empty($_navMyWorkers)): ?>
                <div class="flex items-center gap-1.5 px-2.5 py-1.5 bg-blue-50 rounded-full">
                    <i class="fa-solid fa-user-clock text-[9px] text-blue-500"></i>
                    <span class="text-[10px] font-bold text-blue-700">Ma:</span>
                    <span class="text-[10px] text-blue-600"><?= e($_navMyWorkers) ?></span>
                </div>
            <?php else: ?>
                <div class="flex items-center gap-1.5 px-2.5 py-1.5 bg-gray-100 rounded-full">
                    <i class="fa-solid fa-user-clock text-[9px] text-gray-400"></i>
                    <span class="text-[10px] text-gray-400">Nincs beosztás</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right: Nap zárása + Feladatok + Értesítések + Profil -->
        <div class="flex items-center gap-1.5">

            <!-- Nap zárása gomb -->
            <button onclick="openDayClose()" class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl transition-colors animate-pulse-slow bg-amber-50 border border-amber-200 hover:bg-amber-100" title="Nap zárása">
                <i class="fa-solid fa-lock text-sm text-amber-600"></i>
                <span class="text-xs font-bold text-amber-700 hidden sm:inline">Nap zárása</span>
            </button>
            <style>.animate-pulse-slow { animation: pulseGlow 2s ease-in-out infinite; } @keyframes pulseGlow { 0%,100% { box-shadow: 0 0 0 0 rgba(245,158,11,0); } 50% { box-shadow: 0 0 8px 2px rgba(245,158,11,0.3); } }</style>

            <!-- Feladat jelző -->
            <div class="relative" id="task-wrapper">
                <button class="relative p-2 text-gray-500 hover:bg-gray-200/50 rounded-xl transition-colors" id="task-btn" onclick="toggleTaskDropdown()">
                    <i class="fa-solid fa-clipboard-check"></i>
                    <span id="task-badge" class="hidden absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center px-1 border-2 border-surface">0</span>
                </button>

                <div id="task-dropdown" class="hidden absolute right-0 top-full mt-2 w-80 max-w-[calc(100vw-2rem)] bg-white rounded-xl shadow-xl border border-gray-200 overflow-hidden z-50">
                    <div class="px-4 py-3 bg-sidebar text-white flex items-center justify-between">
                        <span class="font-heading font-bold text-xs">Napi feladatok</span>
                        <span id="task-count-label" class="text-[9px] text-accent font-bold uppercase tracking-wider"></span>
                    </div>
                    <div id="task-list" class="max-h-[400px] overflow-y-auto divide-y divide-gray-100">
                        <div class="p-4 text-center text-xs text-gray-400">Betöltés...</div>
                    </div>
                </div>
            </div>

            <!-- Értesítések -->
            <button class="relative p-2 text-gray-500 hover:bg-gray-200/50 rounded-xl transition-colors" id="notification-btn">
                <i class="fa-solid fa-bell"></i>
                <span id="notification-badge" class="hidden absolute top-1 right-1 h-2.5 w-2.5 bg-red-500 rounded-full border-2 border-surface"></span>
            </button>

            <div class="h-5 w-px bg-gray-200"></div>

            <!-- Profil + Kijelentkezés -->
            <div class="flex items-center gap-2">
                <div class="h-8 w-8 rounded-full bg-sidebar text-accent flex items-center justify-center font-heading font-bold text-[10px]">
                    <?= e(mb_substr($currentUser['name'], 0, 2)) ?>
                </div>
                <div class="hidden sm:block">
                    <p class="text-xs font-semibold text-gray-900 leading-tight"><?= e($currentUser['name']) ?></p>
                    <p class="text-[9px] text-gray-500 uppercase tracking-wider font-bold">
                        <?= $isOwner ? 'Tulajdonos' : (Auth::isAccountant() ? 'Könyvelő' : 'Bolt fiók') ?>
                    </p>
                </div>
                <form method="POST" action="<?= base_url('/logout') ?>" class="ml-1">
                    <?= csrf_field() ?>
                    <button type="submit" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-colors" title="Kijelentkezés">
                        <i class="fa-solid fa-right-from-bracket text-sm"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>

<!-- Feladat jelző JavaScript -->
<script>
(function() {
    const baseUrl = '<?= base_url('') ?>';
    let taskDropdownOpen = false;

    window.toggleTaskDropdown = function() {
        const dd = document.getElementById('task-dropdown');
        taskDropdownOpen = !taskDropdownOpen;
        dd.classList.toggle('hidden', !taskDropdownOpen);
        if (taskDropdownOpen) loadTasks();
    };

    document.addEventListener('click', function(e) {
        if (!document.getElementById('task-wrapper').contains(e.target)) {
            document.getElementById('task-dropdown').classList.add('hidden');
            taskDropdownOpen = false;
        }
    });

    async function loadTasks() {
        try {
            const res = await fetch(baseUrl + '/tasks/api');
            const data = await res.json();

            const badge = document.getElementById('task-badge');
            const countLabel = document.getElementById('task-count-label');
            const list = document.getElementById('task-list');
            const pending = data.count || 0;

            if (pending > 0) { badge.textContent = pending; badge.classList.remove('hidden'); }
            else { badge.classList.add('hidden'); }

            countLabel.textContent = pending > 0 ? pending + ' hiányzik' : 'Minden kész!';

            if (!data.tasks || data.tasks.length === 0) {
                list.innerHTML = '<div class="p-4 text-center text-xs text-gray-400"><i class="fa-solid fa-circle-check text-green-500 text-lg mb-1 block"></i>Nincs feladat.</div>';
                return;
            }

            let html = '', currentSection = '';
            data.tasks.forEach(function(t) {
                let section = t.overdue ? 'overdue' : 'today';
                if (section !== currentSection) {
                    currentSection = section;
                    if (section === 'overdue') {
                        html += '<div class="px-4 py-2 bg-red-50 text-[10px] font-bold text-red-600 uppercase tracking-widest flex items-center gap-1"><i class="fa-solid fa-triangle-exclamation text-[9px]"></i> Elmaradt</div>';
                    } else if (html.includes('overdue')) {
                        html += '<div class="px-4 py-2 bg-surface-container-low text-[10px] font-bold text-on-surface-variant uppercase tracking-widest">Mai feladatok</div>';
                    }
                }
                const iconColor = t.done ? 'text-green-500' : (t.overdue ? 'text-red-500' : 'text-gray-400');
                const checkIcon = t.done ? 'fa-circle-check' : 'fa-circle';
                const textClass = t.done ? 'line-through text-gray-400' : (t.overdue ? 'text-red-700 font-medium' : 'text-gray-700');
                const bgClass = t.overdue && !t.done ? 'bg-red-50/50' : '';
                html += '<a href="' + baseUrl + t.link + '" class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 transition-colors ' + bgClass + '">' +
                    '<i class="fa-solid ' + checkIcon + ' ' + iconColor + '"></i>' +
                    '<div class="flex-1 min-w-0"><p class="text-xs ' + textClass + ' truncate">' + escapeHtml(t.text) + '</p></div>' +
                    '<i class="fa-solid ' + t.icon + ' text-[10px] text-gray-300"></i></a>';
            });
            list.innerHTML = html;
        } catch(e) {}
    }

    function escapeHtml(s) { const d = document.createElement('div'); d.textContent = s||''; return d.innerHTML; }

    loadTasks();
    setInterval(function() { if (!taskDropdownOpen) loadTasks(); }, 60000);

    // === Nap zárása (szép modal) ===
    let dayCloseData = null;

    window.openDayClose = async function() {
        try {
            const res = await fetch(baseUrl + '/day-close/check');
            dayCloseData = await res.json();
            const missing = dayCloseData.missing || [];

            const overlay = document.getElementById('dayclose-overlay');
            const content = document.getElementById('dayclose-content');

            if (missing.length === 0) {
                content.innerHTML = `
                    <div class="text-center py-6">
                        <div class="w-20 h-20 rounded-full bg-emerald-100 flex items-center justify-center mx-auto mb-4">
                            <i class="fa-solid fa-circle-check text-4xl text-emerald-500"></i>
                        </div>
                        <h3 class="font-heading font-bold text-xl text-gray-900 mb-2">Minden kész!</h3>
                        <p class="text-sm text-gray-500 mb-6">Az összes mai feladat ki van töltve. A nap lezárható.</p>
                        <button onclick="closeDayCloseModal()" class="px-6 py-3 bg-sidebar text-accent font-bold rounded-full text-sm">Bezárás</button>
                    </div>`;
            } else {
                let itemsHtml = '';
                missing.forEach(m => {
                    const iconMap = {
                        'napi_keszpenz': 'fa-money-bill text-emerald-500',
                        'napi_bankkartya': 'fa-credit-card text-blue-500',
                        'ertekeles': 'fa-star text-amber-500',
                        'selejt_ertek': 'fa-coins text-orange-500',
                        'selejt_befizetes': 'fa-box-open text-purple-500',
                    };
                    const icon = iconMap[m.type] || 'fa-circle text-gray-400';
                    itemsHtml += `<div class="flex items-center gap-3 px-4 py-2.5 bg-red-50/50 rounded-xl">
                        <i class="fa-solid ${icon} text-sm"></i>
                        <div class="flex-1">
                            <span class="text-sm font-medium text-gray-900">${escapeHtml(m.label)}</span>
                            <span class="text-xs text-gray-400 ml-1">— ${escapeHtml(m.store)}</span>
                        </div>
                        <span class="text-xs font-bold text-red-500">0</span>
                    </div>`;
                });

                content.innerHTML = `
                    <div class="text-center mb-5">
                        <div class="w-16 h-16 rounded-full bg-amber-100 flex items-center justify-center mx-auto mb-3">
                            <i class="fa-solid fa-lock text-2xl text-amber-600"></i>
                        </div>
                        <h3 class="font-heading font-bold text-xl text-gray-900">Nap zárása</h3>
                        <p class="text-xs text-gray-500 mt-1">${dayCloseData.date || ''}</p>
                    </div>
                    <div class="mb-4">
                        <p class="text-sm text-gray-600 mb-3 flex items-center gap-2">
                            <i class="fa-solid fa-triangle-exclamation text-amber-500"></i>
                            <span><strong>${missing.length} feladat</strong> nincs kitöltve. 0 értékkel rögzítjük?</span>
                        </p>
                        <div class="space-y-1.5 max-h-[300px] overflow-y-auto">${itemsHtml}</div>
                    </div>
                    <div class="flex gap-3">
                        <button onclick="confirmDayClose()" class="flex-1 px-6 py-3 bg-sidebar text-accent font-bold rounded-full text-sm flex items-center justify-center gap-2 hover:bg-gray-800 transition-colors">
                            <i class="fa-solid fa-lock"></i> Lezárás (0 értékkel)
                        </button>
                        <button onclick="closeDayCloseModal()" class="px-6 py-3 text-gray-500 hover:text-gray-700 font-medium text-sm">
                            Mégse
                        </button>
                    </div>`;
            }

            overlay.classList.remove('hidden');
        } catch(e) {}
    };

    window.closeDayCloseModal = function() {
        document.getElementById('dayclose-overlay').classList.add('hidden');
    };

    window.confirmDayClose = async function() {
        const btn = event.target.closest('button');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Rögzítés...';

        try {
            const closeRes = await fetch(baseUrl + '/day-close/close', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken()},
                body: JSON.stringify({items: dayCloseData.missing, _csrf: getCsrfToken()})
            });
            const closeData = await closeRes.json();

            const content = document.getElementById('dayclose-content');
            content.innerHTML = `
                <div class="text-center py-6">
                    <div class="w-20 h-20 rounded-full bg-emerald-100 flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-circle-check text-4xl text-emerald-500"></i>
                    </div>
                    <h3 class="font-heading font-bold text-xl text-gray-900 mb-2">Nap lezárva!</h3>
                    <p class="text-sm text-gray-500 mb-6">${closeData.count} feladat rögzítve 0 értékkel.</p>
                    <button onclick="closeDayCloseModal()" class="px-6 py-3 bg-sidebar text-accent font-bold rounded-full text-sm">Bezárás</button>
                </div>`;

            loadTasks();
        } catch(e) {}
    };

    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }
})();
</script>
