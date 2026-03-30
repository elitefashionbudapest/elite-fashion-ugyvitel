<?php
use App\Core\Auth;

$stores = $data['stores'] ?? [];
$employees = $data['employees'] ?? [];
$currentStoreId = $data['currentStoreId'] ?? null;
$isOwner = Auth::isOwner();
?>

<div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-6">
    <div>
        <h1 class="text-3xl font-heading font-extrabold text-on-surface tracking-tight mb-1">Beosztás</h1>
        <p class="text-on-surface-variant text-sm">Válassz boltot és dolgozót, majd kattints a napokra.</p>
    </div>
    <div class="flex gap-2 no-print">
        <button onclick="window.print()" class="px-5 py-2.5 bg-secondary-container text-on-secondary-container font-semibold rounded-full flex items-center gap-2 hover:bg-surface-variant transition-colors text-sm">
            <i class="fa-solid fa-print"></i> Nyomtatás
        </button>
    </div>
</div>

<!-- Választók + Státusz sáv -->
<div class="bg-surface-container-lowest rounded-xl p-4 mb-4 flex flex-wrap gap-4 items-end no-print">
    <div class="flex-1 min-w-[180px]">
        <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-widest mb-1">Bolt</label>
        <select id="store-select" class="w-full px-4 py-3 border border-outline-variant rounded-xl text-sm font-medium focus:ring-2 focus:ring-primary-container focus:border-primary bg-surface-container-lowest">
            <?php foreach ($stores as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $s['id'] == $currentStoreId ? 'selected' : '' ?>><?= e($s['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="flex-1 min-w-[200px]">
        <label class="block text-[10px] font-bold text-on-surface-variant uppercase tracking-widest mb-1">Dolgozó</label>
        <select id="employee-select" class="w-full px-4 py-3 border border-outline-variant rounded-xl text-sm font-medium focus:ring-2 focus:ring-primary-container focus:border-primary bg-surface-container-lowest">
            <option value="">— Válassz dolgozót —</option>
            <?php foreach ($employees as $emp): ?>
                <option value="<?= $emp['id'] ?>"><?= e($emp['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="flex items-center gap-2">
        <button id="prev-month" class="p-2.5 hover:bg-surface-container rounded-xl transition-colors text-on-surface-variant">
            <i class="fa-solid fa-chevron-left"></i>
        </button>
        <span id="current-month" class="font-heading font-bold text-on-surface text-sm min-w-[140px] text-center"></span>
        <button id="next-month" class="p-2.5 hover:bg-surface-container rounded-xl transition-colors text-on-surface-variant">
            <i class="fa-solid fa-chevron-right"></i>
        </button>
    </div>
</div>

<!-- Státusz + Szerkesztés sáv -->
<div id="status-bar" class="mb-4 rounded-xl p-3 flex items-center justify-between no-print hidden">
    <div class="flex items-center gap-2">
        <i id="status-icon" class="text-sm"></i>
        <span id="status-text" class="text-sm font-medium"></span>
    </div>
    <div class="flex gap-2" id="status-actions"></div>
</div>

<!-- Kiválasztott dolgozó összesítő -->
<div id="monthly-summary" class="mb-4 no-print hidden">
    <div class="bg-surface-container-lowest rounded-xl p-4 flex flex-wrap items-center gap-4" id="summary-content"></div>
</div>

<!-- Jelmagyarázat -->
<div id="legend" class="hidden mb-4 flex flex-wrap gap-4 text-xs no-print">
    <div class="flex items-center gap-1.5">
        <span class="w-4 h-4 rounded bg-[#D9FF54]/50 border-2 border-[#506300]"></span>
        <span class="text-on-surface-variant font-medium">Dolgozik ebben a boltban</span>
    </div>
    <div class="flex items-center gap-1.5">
        <span class="w-4 h-4 rounded bg-orange-200 border-2 border-orange-400"></span>
        <span class="text-on-surface-variant font-medium">Más boltban dolgozik</span>
    </div>
    <div class="flex items-center gap-1.5">
        <span class="w-4 h-4 rounded bg-red-200 border-2 border-red-400"></span>
        <span class="text-on-surface-variant font-medium">Szabadságon</span>
    </div>
    <div class="flex items-center gap-1.5">
        <span class="w-4 h-4 rounded bg-gray-100 border-2 border-gray-300"></span>
        <span class="text-on-surface-variant font-medium">Szabadnap</span>
    </div>
    <div class="flex items-center gap-1.5">
        <span class="w-4 h-4 rounded bg-purple-200 border-2 border-purple-400"></span>
        <span class="text-on-surface-variant font-medium">Ünnepnap (zárva)</span>
    </div>
</div>

<!-- Naptár -->
<div id="calendar-container" class="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden">
    <div class="p-8 text-center text-on-surface-variant text-sm">
        <i class="fa-solid fa-calendar-days text-4xl text-outline-variant mb-3 block"></i>
        Válassz dolgozót a beosztás szerkesztéséhez.
    </div>
</div>

<input type="hidden" id="base-url" value="<?= base_url('') ?>">
<input type="hidden" id="is-owner" value="<?= $isOwner ? '1' : '0' ?>">

<script>
(function() {
    const baseUrl = document.getElementById('base-url').value;
    const isOwner = document.getElementById('is-owner').value === '1';
    const storeSelect = document.getElementById('store-select');
    const employeeSelect = document.getElementById('employee-select');
    const container = document.getElementById('calendar-container');
    const monthLabel = document.getElementById('current-month');
    const legend = document.getElementById('legend');
    const statusBar = document.getElementById('status-bar');

    let currentYear = new Date().getFullYear();
    let currentMonth = new Date().getMonth();
    let scheduleData = {};
    let vacationDays = new Set();
    let holidayData = {}; // { 'YYYY-MM-DD': 'Ünnep neve' }
    let loading = false;
    let editMode = false;
    let monthStatus = 'draft'; // draft, approved, modified

    const dayNames = ['Hé', 'Ke', 'Sze', 'Csü', 'Pé', 'Szo', 'Va'];
    const monthNames = ['Január', 'Február', 'Március', 'Április', 'Május', 'Június',
                         'Július', 'Augusztus', 'Szeptember', 'Október', 'November', 'December'];

    function getStoreId() { return storeSelect.value || storeSelect.getAttribute('value'); }
    function getEmployeeId() { return employeeSelect.value; }
    function updateMonthLabel() { monthLabel.textContent = currentYear + '. ' + monthNames[currentMonth]; }
    function escapeHtml(s) { const d = document.createElement('div'); d.textContent = s||''; return d.innerHTML; }

    // Státusz betöltés
    async function loadStatus() {
        const storeId = getStoreId();
        if (!storeId) return;
        try {
            const res = await fetch(baseUrl + '/schedule/api/status?store_id=' + storeId + '&year=' + currentYear + '&month=' + (currentMonth + 1));
            const data = await res.json();
            monthStatus = data.status || 'draft';
            updateStatusBar();
        } catch(e) { monthStatus = 'draft'; updateStatusBar(); }
    }

    function updateStatusBar() {
        const icon = document.getElementById('status-icon');
        const text = document.getElementById('status-text');
        const actions = document.getElementById('status-actions');

        statusBar.classList.remove('hidden', 'bg-gray-100', 'bg-emerald-50', 'bg-amber-50', 'border', 'border-emerald-200', 'border-amber-200', 'border-gray-200');

        if (monthStatus === 'approved') {
            statusBar.classList.add('bg-emerald-50', 'border', 'border-emerald-200');
            icon.className = 'fa-solid fa-circle-check text-sm text-emerald-600';
            text.className = 'text-sm font-medium text-emerald-700';
            text.textContent = 'Jóváhagyott beosztás';
            editMode = false;

            let btns = '<button onclick="enableEdit()" class="px-4 py-2 bg-amber-500 text-white rounded-full text-xs font-bold hover:bg-amber-600 transition-colors"><i class="fa-solid fa-pen mr-1"></i>Szerkesztés</button>';
            actions.innerHTML = btns;

        } else if (monthStatus === 'modified') {
            statusBar.classList.add('bg-amber-50', 'border', 'border-amber-200');
            icon.className = 'fa-solid fa-triangle-exclamation text-sm text-amber-600';
            text.className = 'text-sm font-medium text-amber-700';
            text.textContent = 'Módosítva – tulajdonosi elfogadásra vár';
            editMode = true;

            let btns = '';
            if (isOwner) {
                btns = '<button onclick="approveMonth()" class="px-4 py-2 bg-emerald-600 text-white rounded-full text-xs font-bold hover:bg-emerald-700 transition-colors"><i class="fa-solid fa-check mr-1"></i>Módosítás elfogadása</button>';
            }
            actions.innerHTML = btns;

        } else {
            // draft - szabadon szerkeszthető, nem kell jóváhagyni
            statusBar.classList.add('bg-gray-100', 'border', 'border-gray-200');
            icon.className = 'fa-solid fa-pen-to-square text-sm text-gray-500';
            text.className = 'text-sm font-medium text-gray-600';
            text.textContent = 'Szabadon szerkeszthető';
            editMode = true;

            let btns = '';
            if (isOwner) {
                btns = '<button onclick="approveMonth()" class="px-4 py-2 bg-emerald-600 text-white rounded-full text-xs font-bold hover:bg-emerald-700 transition-colors"><i class="fa-solid fa-lock mr-1"></i>Zárolás és jóváhagyás</button>';
            }
            actions.innerHTML = btns;
        }

        statusBar.classList.remove('hidden');
    }

    window.enableEdit = function() {
        const reason = prompt('Miért módosítja a jóváhagyott beosztást?');
        if (reason === null) return;

        fetch(baseUrl + '/schedule/api/request-modify', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
            body: JSON.stringify({ store_id: parseInt(getStoreId()), year: currentYear, month: currentMonth + 1, reason: reason })
        }).then(() => {
            monthStatus = 'modified';
            editMode = true;
            updateStatusBar();
            render();
        });
    };

    window.approveMonth = function() {
        if (!confirm('Biztosan jóváhagyja a(z) ' + currentYear + '. ' + monthNames[currentMonth] + ' beosztást?')) return;

        fetch(baseUrl + '/schedule/api/approve', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
            body: JSON.stringify({ store_id: parseInt(getStoreId()), year: currentYear, month: currentMonth + 1 })
        }).then(() => {
            monthStatus = 'approved';
            editMode = false;
            updateStatusBar();
            render();
        });
    };

    async function loadData() {
        const empId = getEmployeeId();
        if (!empId) return;

        const first = currentYear + '-' + String(currentMonth+1).padStart(2,'0') + '-01';
        const lastDate = new Date(currentYear, currentMonth+1, 0);
        const last = currentYear + '-' + String(currentMonth+1).padStart(2,'0') + '-' + String(lastDate.getDate()).padStart(2,'0');

        try {
            const res = await fetch(baseUrl + '/schedule/api/employee?employee_id=' + empId + '&start=' + first + '&end=' + last);
            const data = await res.json();
            scheduleData = {};
            vacationDays = new Set();
            holidayData = {};
            (data.schedules||[]).forEach(s => { scheduleData[s.work_date] = { storeId: parseInt(s.store_id), storeName: s.store_name, id: s.id }; });
            (data.vacations||[]).forEach(v => { let d = new Date(v.date_from); const end = new Date(v.date_to); while (d <= end) { vacationDays.add(d.toISOString().split('T')[0]); d.setDate(d.getDate()+1); } });
            (data.holidays||[]).forEach(h => { holidayData[h.date] = h.name; });
            render();
        } catch(e) { console.error(e); }
    }

    function render() {
        const empId = getEmployeeId();
        const storeId = parseInt(getStoreId());

        if (!empId) {
            container.innerHTML = '<div class="p-8 text-center text-on-surface-variant text-sm"><i class="fa-solid fa-calendar-days text-4xl text-outline-variant mb-3 block"></i>Válassz dolgozót a beosztás szerkesztéséhez.</div>';
            legend.classList.add('hidden');
            return;
        }
        legend.classList.remove('hidden');

        const firstDay = new Date(currentYear, currentMonth, 1);
        const lastDay = new Date(currentYear, currentMonth+1, 0);
        let startWD = firstDay.getDay();
        startWD = startWD === 0 ? 6 : startWD - 1;

        const today = new Date().toISOString().split('T')[0];
        const canEdit = editMode;
        let html = '<div class="grid grid-cols-7">';

        dayNames.forEach((d, i) => {
            html += '<div class="px-2 py-3 text-center text-[10px] font-bold uppercase tracking-widest ' +
                    (i>=5 ? 'text-red-400' : 'text-on-surface-variant') +
                    ' border-b border-surface-container bg-surface-container-low">' + d + '</div>';
        });

        for (let i = 0; i < startWD; i++) html += '<div class="border-b border-r border-surface-container/50 p-2 min-h-[72px] bg-surface/30"></div>';

        for (let day = 1; day <= lastDay.getDate(); day++) {
            const ds = currentYear + '-' + String(currentMonth+1).padStart(2,'0') + '-' + String(day).padStart(2,'0');
            const wd = (startWD + day - 1) % 7;
            const isWE = wd >= 5, isToday = ds === today, isVac = vacationDays.has(ds);
            const isHoliday = holidayData[ds] !== undefined;
            const sch = scheduleData[ds], here = sch && sch.storeId === storeId, elsewhere = sch && sch.storeId !== storeId;

            let cls = 'border-b border-r border-surface-container/50 p-2 min-h-[72px] transition-all relative select-none ';
            if (isHoliday) cls += 'cursor-not-allowed ';
            else if (!canEdit) cls += 'cursor-default ';
            else if (isVac) cls += 'cursor-not-allowed ';
            else cls += 'cursor-pointer ';

            if (isHoliday) cls += 'bg-purple-100/80 ';
            else if (isVac) cls += 'bg-red-100/80 ';
            else if (here) cls += 'bg-[#D9FF54]/40 ' + (canEdit ? 'hover:bg-[#D9FF54]/50 ' : '');
            else if (elsewhere) cls += 'bg-orange-100/80 ' + (canEdit ? 'hover:bg-orange-200/80 ' : '');
            else cls += (isWE ? 'bg-gray-50 ' : 'bg-white ') + (canEdit ? 'hover:bg-surface-container-low ' : '');

            const canClick = canEdit && !isHoliday && !isVac;
            const onclick = canClick ? ' onclick="toggleDay(\'' + ds + '\')"' : '';
            html += '<div class="' + cls + '"' + onclick + '>';

            if (isToday) html += '<span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-sidebar text-accent text-sm font-bold">' + day + '</span>';
            else if (isHoliday) html += '<span class="text-sm font-bold text-purple-600">' + day + '</span>';
            else html += '<span class="text-sm font-bold ' + (isWE ? 'text-red-400' : 'text-on-surface') + '">' + day + '</span>';

            const isDayOff = !isHoliday && !isVac && !here && !elsewhere && monthStatus === 'approved';

            if (isHoliday) html += '<div class="mt-1"><span class="text-[9px] font-bold text-purple-600"><i class="fa-solid fa-flag text-[8px] mr-0.5"></i>' + escapeHtml(holidayData[ds]) + '</span></div>';
            else if (isVac) html += '<div class="mt-1"><span class="text-[9px] font-bold text-red-500"><i class="fa-solid fa-umbrella-beach text-[8px] mr-0.5"></i>Szabadság</span></div>';
            else if (here) html += '<div class="mt-1"><span class="text-[9px] font-bold text-[#506300]"><i class="fa-solid fa-check text-[8px] mr-0.5"></i>Dolgozik</span></div>';
            else if (elsewhere) html += '<div class="mt-1"><span class="text-[9px] font-bold text-orange-600"><i class="fa-solid fa-store text-[8px] mr-0.5"></i>' + escapeHtml(sch.storeName) + '</span></div>';
            else if (isDayOff) html += '<div class="mt-1"><span class="text-[9px] font-bold text-gray-400"><i class="fa-solid fa-moon text-[8px] mr-0.5"></i>Szabadnap</span></div>';

            // Lakat ikon ha nem szerkeszthető
            if (!canEdit && !isVac && !isHoliday && (here || elsewhere)) {
                html += '<div class="absolute top-1 right-1"><i class="fa-solid fa-lock text-[8px] text-gray-300"></i></div>';
            }

            html += '</div>';
        }

        const total = startWD + lastDay.getDate();
        const rem = total % 7 === 0 ? 0 : 7 - (total % 7);
        for (let i = 0; i < rem; i++) html += '<div class="border-b border-r border-surface-container/50 p-2 min-h-[72px] bg-surface/30"></div>';

        html += '</div>';
        container.innerHTML = html;
    }

    window.toggleDay = async function(dateStr) {
        const empId = getEmployeeId(), storeId = parseInt(getStoreId());
        if (!empId || loading || !editMode) return;
        if (vacationDays.has(dateStr)) return;
        if (holidayData[dateStr]) return;

        const sch = scheduleData[dateStr];
        loading = true;

        try {
            if (sch && sch.storeId === storeId) {
                await fetch(baseUrl + '/schedule/api/' + sch.id + '/delete', { method: 'POST', headers: { 'X-CSRF-TOKEN': getCsrfToken() } });
                delete scheduleData[dateStr];
                render();
            } else if (sch && sch.storeId !== storeId) {
                if (!confirm('Ez a dolgozó már a(z) ' + sch.storeName + ' boltban dolgozik ezen a napon.\nÁtrakod ide?')) { loading = false; return; }
                await fetch(baseUrl + '/schedule/api/' + sch.id + '/delete', { method: 'POST', headers: { 'X-CSRF-TOKEN': getCsrfToken() } });
                const res = await fetch(baseUrl + '/schedule/api', {
                    method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
                    body: JSON.stringify({ store_id: storeId, employee_id: parseInt(empId), work_date: dateStr })
                });
                await loadData(); await loadStatus();
            } else {
                const res = await fetch(baseUrl + '/schedule/api', {
                    method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
                    body: JSON.stringify({ store_id: storeId, employee_id: parseInt(empId), work_date: dateStr })
                });
                const text = await res.text();
                try {
                    const data = JSON.parse(text);
                    if (data.success) { await loadData(); await loadStatus(); }
                    else alert(data.error || 'Hiba történt.');
                } catch(e) { alert('Szerver hiba: ' + text.substring(0, 200)); }
            }
        } catch(e) { console.error(e); alert('Hálózati hiba: ' + e.message); }
        loading = false;
    };

    document.getElementById('prev-month').addEventListener('click', () => {
        currentMonth--; if (currentMonth < 0) { currentMonth = 11; currentYear--; }
        updateMonthLabel(); loadStatus(); loadSummary(); if (getEmployeeId()) loadData(); else render();
    });
    document.getElementById('next-month').addEventListener('click', () => {
        currentMonth++; if (currentMonth > 11) { currentMonth = 0; currentYear++; }
        updateMonthLabel(); loadStatus(); loadSummary(); if (getEmployeeId()) loadData(); else render();
    });
    employeeSelect.addEventListener('change', () => { renderSummary(); if (employeeSelect.value) loadData(); else { scheduleData = {}; render(); } });
    if (storeSelect.tagName === 'SELECT') {
        storeSelect.addEventListener('change', () => { loadStatus(); if (getEmployeeId()) loadData(); else render(); });
    }

    let summaryCache = null;

    async function loadSummary() {
        try {
            const res = await fetch(baseUrl + '/schedule/api/summary?year=' + currentYear + '&month=' + (currentMonth + 1));
            summaryCache = await res.json();
            renderSummary();
        } catch(e) { console.error(e); }
    }

    function renderSummary() {
        const panel = document.getElementById('monthly-summary');
        const content = document.getElementById('summary-content');
        const empId = getEmployeeId();

        if (!summaryCache || !summaryCache.employees || !empId) {
            panel.classList.add('hidden');
            return;
        }

        const emp = summaryCache.employees.find(e => e.id == empId);
        if (!emp) { panel.classList.add('hidden'); return; }

        panel.classList.remove('hidden');

        const remainColor = emp.yearly_remaining <= 0 ? 'bg-red-100 text-red-700' : (emp.yearly_remaining <= 5 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700');

        content.innerHTML =
            '<div class="flex items-center gap-2 mr-2">' +
                '<div class="w-8 h-8 rounded-full bg-secondary-container flex items-center justify-center text-on-secondary-container font-bold text-[10px]">' + escapeHtml(emp.name.substring(0,2)) + '</div>' +
                '<span class="text-sm font-heading font-bold text-on-surface">' + escapeHtml(emp.name) + '</span>' +
            '</div>' +
            '<div class="flex items-center gap-1.5 px-3 py-1.5 bg-[#D9FF54]/20 rounded-full">' +
                '<i class="fa-solid fa-briefcase text-[10px] text-[#506300]"></i>' +
                '<span class="text-xs font-bold text-[#506300]">' + emp.work_days + '</span>' +
                '<span class="text-[10px] text-[#506300]/70">munkanap</span>' +
            '</div>' +
            '<div class="flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 rounded-full">' +
                '<i class="fa-solid fa-moon text-[10px] text-gray-500"></i>' +
                '<span class="text-xs font-bold text-gray-600">' + emp.free_days + '</span>' +
                '<span class="text-[10px] text-gray-400">szabadnap</span>' +
            '</div>' +
            '<div class="flex items-center gap-1.5 px-3 py-1.5 bg-red-100 rounded-full">' +
                '<i class="fa-solid fa-umbrella-beach text-[10px] text-red-500"></i>' +
                '<span class="text-xs font-bold text-red-600">' + emp.vacation_days + '</span>' +
                '<span class="text-[10px] text-red-400">szabadság</span>' +
            '</div>' +
            '<div class="h-5 w-px bg-gray-300 mx-1"></div>' +
            '<div class="flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 rounded-full">' +
                '<span class="text-[10px] text-blue-500">Éves:</span>' +
                '<span class="text-xs font-bold text-blue-700">' + emp.yearly_used + '/' + emp.yearly_total + '</span>' +
            '</div>' +
            '<div class="flex items-center gap-1.5 px-3 py-1.5 ' + remainColor + ' rounded-full">' +
                '<span class="text-[10px]">Maradt:</span>' +
                '<span class="text-xs font-bold">' + emp.yearly_remaining + ' nap</span>' +
            '</div>' +
            (emp.payslip_url ?
                '<a href="' + emp.payslip_url + '" target="_blank" class="flex items-center gap-1.5 px-3 py-1.5 bg-purple-100 rounded-full hover:bg-purple-200 transition-colors" title="Bérpapír letöltése">' +
                    '<i class="fa-solid fa-file-contract text-[10px] text-purple-600"></i>' +
                    '<span class="text-xs font-bold text-purple-700">Bérpapír</span>' +
                '</a>' : '');
    }

    updateMonthLabel(); loadStatus(); loadSummary(); render();
})();
</script>

<style>@media print {
    .no-print, nav, header, #sidebar-overlay { display: none !important; }
    body, main { overflow: visible !important; }
    #calendar-container { box-shadow: none !important; }
}</style>
