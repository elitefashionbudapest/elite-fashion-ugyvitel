<?php
use App\Core\Auth;

$items   = $data['items'] ?? [];
$stores  = $data['stores'] ?? [];
$filters = $data['filters'] ?? [];
?>

<div class="flex flex-wrap flex-col md:flex-row md:items-end justify-between gap-6 mb-8">
    <div>
        <h1 class="text-3xl font-heading font-extrabold text-on-surface tracking-tight mb-1">Selejt kezelés</h1>
        <p class="text-on-surface-variant text-sm">Vonalkód olvasóval szkennelj, automatikus mentés.</p>
    </div>
    <a href="<?= base_url('/defects/export?' . http_build_query(array_filter($filters))) ?>" class="px-6 py-3 bg-secondary-container text-on-secondary-container font-semibold rounded-full flex items-center gap-2 hover:bg-surface-variant transition-colors text-sm">
        <i class="fa-solid fa-file-arrow-down"></i> CSV Export
    </a>
</div>

<!-- Szűrők -->
<div class="bg-surface-container-low p-4 rounded-lg flex flex-wrap items-center gap-4 mb-6">
    <span class="text-xs font-bold text-on-surface-variant uppercase tracking-widest">Szűrők</span>
    <form method="GET" action="<?= base_url('/defects') ?>" class="flex flex-wrap gap-3 items-center flex-1">
        <?php if (Auth::isOwner()): ?>
        <select name="store_id" id="store-selector" class="bg-surface-container-lowest border-none rounded-full text-xs font-semibold py-2 px-4 focus:ring-2 focus:ring-primary-container cursor-pointer">
            <option value="">Minden bolt</option>
            <?php foreach ($stores as $s): ?>
                <option value="<?= $s['id'] ?>" <?= ($filters['store_id'] ?? '') == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <input type="date" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>" class="bg-surface-container-lowest border-none rounded-full text-xs font-semibold py-2 px-4 focus:ring-2 focus:ring-primary-container">
        <input type="date" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>" class="bg-surface-container-lowest border-none rounded-full text-xs font-semibold py-2 px-4 focus:ring-2 focus:ring-primary-container">
        <button type="submit" class="px-5 py-2 bg-secondary-container text-on-secondary-container font-semibold rounded-full text-xs hover:bg-surface-variant transition-colors">Szűrés</button>
    </form>
</div>

<!-- Fő tartalom -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- BAL: Vonalkód bevitel -->
    <div class="lg:col-span-1">
        <div class="bg-surface-container-lowest rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-heading font-bold text-on-surface text-sm flex items-center gap-2">
                    <i class="fa-solid fa-barcode text-primary"></i>
                    Vonalkód szkenner
                </h3>
                <div id="auto-save-indicator" class="flex items-center gap-1.5 text-[10px] font-bold text-green-600 uppercase tracking-wider">
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    Automata mentés
                </div>
            </div>

            <!-- Vonalkód beviteli mező -->
            <div class="mb-4">
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-2">Szkennelj vagy írd be a vonalkódot</label>
                <input type="text"
                       id="barcode-input"
                       placeholder="Vonalkód beolvasása..."
                       class="w-full px-4 py-4 border-2 border-primary-container rounded-xl text-lg font-mono font-bold text-center focus:ring-2 focus:ring-accent/30 focus:border-accent bg-surface-container-lowest"
                       autocomplete="off"
                       autofocus>
                <p class="text-[10px] text-on-surface-variant mt-2 text-center">
                    A vonalkód olvasó automatikusan Enter-t küld, az mentés után törlődik a mező.
                </p>
            </div>

            <!-- Utolsó szkennelt -->
            <div id="last-scanned" class="hidden bg-green-50 border border-green-200 rounded-xl p-3 mb-3">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-circle-check text-green-600"></i>
                    <div>
                        <p class="text-[10px] text-green-600 font-bold uppercase tracking-wider">Utolsó mentett vonalkód</p>
                        <p id="last-scanned-code" class="font-mono font-bold text-green-800 text-lg"></p>
                    </div>
                </div>
            </div>

            <!-- Hiba -->
            <div id="scanner-error" class="hidden bg-red-50 border border-red-200 rounded-xl p-3">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation text-red-600"></i>
                    <p id="scanner-error-text" class="text-xs text-red-700"></p>
                </div>
            </div>

            <!-- Összesítő -->
            <div class="mt-4 bg-surface-container rounded-xl p-4 text-center">
                <p class="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest mb-1">Mai selejtek</p>
                <p id="item-count" class="text-3xl font-heading font-extrabold text-on-surface"><?= count($items) ?></p>
                <p class="text-xs text-on-surface-variant">tétel</p>
            </div>

            <!-- Napi selejt összérték rögzítés -->
            <div class="mt-4 bg-amber-50 border border-amber-200 rounded-xl p-4" id="daily-value-section">
                <p class="text-xs font-bold text-amber-700 uppercase tracking-widest mb-2">
                    <i class="fa-solid fa-coins mr-1"></i>Napi selejt összérték
                </p>
                <form method="POST" action="<?= base_url('/defects/daily-value') ?>" class="flex gap-2 items-end">
                    <?= csrf_field() ?>
                    <input type="hidden" name="value_date" value="<?= date('Y-m-d') ?>">
                    <?php if (Auth::isOwner()): ?>
                    <input type="hidden" name="store_id" id="daily-value-store" value="">
                    <?php endif; ?>
                    <div class="flex-1">
                        <input type="text" inputmode="numeric" data-calc name="total_value" id="daily-value-input"
                               class="w-full px-3 py-2.5 border border-amber-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-400 focus:border-amber-400 bg-white font-bold"
                               placeholder="Összérték (Ft)" required>
                    </div>
                    <button type="submit" class="px-4 py-2.5 bg-amber-600 text-white rounded-lg text-sm font-bold hover:bg-amber-700 transition-colors">
                        <i class="fa-solid fa-check mr-1"></i>Rögzítés
                    </button>
                </form>
                <div id="daily-value-status" class="mt-2 hidden text-xs"></div>
            </div>
        </div>
    </div>

    <!-- JOBB: Selejt lista táblázat -->
    <div class="lg:col-span-2 bg-surface-container-lowest rounded-lg overflow-hidden">
        <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
            <table class="w-full text-left border-collapse" id="defect-table">
                <thead class="sticky top-0 z-10">
                    <tr class="bg-surface-container-low">
                        <th class="px-6 py-4 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Vonalkód</th>
                        <th class="px-6 py-4 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Bolt</th>
                        <th class="px-6 py-4 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Dátum / Idő</th>
                        <?php if (Auth::isOwner()): ?>
                        <th class="px-6 py-4 text-xs font-bold text-on-surface-variant uppercase tracking-widest text-right">Műveletek</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody id="defect-tbody" class="divide-y divide-surface-container">
                    <?php if (empty($items)): ?>
                        <tr id="empty-row"><td colspan="<?= Auth::isOwner() ? 4 : 3 ?>" class="px-8 py-12 text-center text-on-surface-variant">
                            <i class="fa-solid fa-barcode text-4xl mb-2 block text-outline-variant"></i>
                            Nincsenek selejt tételek.
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                        <tr class="hover:bg-surface-container-low/50 transition-colors">
                            <td class="px-6 py-4 font-mono font-bold text-on-surface"><?= e($item['barcode']) ?></td>
                            <td class="px-6 py-4 text-sm text-on-surface-variant"><?= e($item['store_name']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-on-surface-variant"><?= date('Y.m.d H:i', strtotime($item['scanned_at'])) ?></td>
                            <?php if (Auth::isOwner()): ?>
                            <td class="px-6 py-4 text-right">
                                <form method="POST" action="<?= base_url("/defects/{$item['id']}/delete") ?>" class="inline" onsubmit="return confirm('Biztosan törli?')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="p-2 hover:bg-error-container/10 rounded-full transition-colors text-on-surface-variant hover:text-error">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Rejtett adatok -->
<input type="hidden" id="scan-url" value="<?= base_url('/defects/scan') ?>">
<input type="hidden" id="is-owner" value="<?= Auth::isOwner() ? '1' : '0' ?>">

<script>
(function() {
    const barcodeInput = document.getElementById('barcode-input');
    const scanUrl = document.getElementById('scan-url').value;
    const lastScanned = document.getElementById('last-scanned');
    const lastScannedCode = document.getElementById('last-scanned-code');
    const errorDiv = document.getElementById('scanner-error');
    const errorText = document.getElementById('scanner-error-text');
    const itemCount = document.getElementById('item-count');
    const tbody = document.getElementById('defect-tbody');
    const emptyRow = document.getElementById('empty-row');
    const isOwner = document.getElementById('is-owner').value === '1';

    let lastBarcode = '';
    let lastTime = 0;

    // Enter-re automatikus mentés
    barcodeInput.addEventListener('keydown', async function(e) {
        if (e.key !== 'Enter') return;
        e.preventDefault();

        const barcode = barcodeInput.value.trim();
        if (!barcode) return;

        // Dupla szkennelés védelem (3 mp)
        const now = Date.now();
        if (barcode === lastBarcode && (now - lastTime) < 3000) {
            barcodeInput.value = '';
            return;
        }

        lastBarcode = barcode;
        lastTime = now;

        try {
            const res = await fetch(scanUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: JSON.stringify({ barcode: barcode })
            });
            const data = await res.json();

            if (data.success) {
                // Sikeres mentés
                barcodeInput.value = '';
                lastScannedCode.textContent = barcode;
                lastScanned.classList.remove('hidden');
                errorDiv.classList.add('hidden');

                // Sor hozzáadása a táblázathoz
                if (emptyRow) emptyRow.remove();
                const row = document.createElement('tr');
                row.className = 'hover:bg-surface-container-low/50 transition-colors bg-green-50';
                row.innerHTML =
                    '<td class="px-6 py-4 font-mono font-bold text-on-surface">' + escapeHtml(barcode) + '</td>' +
                    '<td class="px-6 py-4 text-sm text-on-surface-variant">' + escapeHtml(data.item?.store_name || '') + '</td>' +
                    '<td class="px-6 py-4 text-sm text-on-surface-variant">' + new Date().toLocaleString('hu-HU') + '</td>' +
                    (isOwner ? '<td class="px-6 py-4 text-right text-sm text-gray-400">Friss</td>' : '');
                tbody.insertBefore(row, tbody.firstChild);

                // Zöld háttér eltüntetése
                setTimeout(() => row.classList.remove('bg-green-50'), 2000);

                // Számláló frissítés
                itemCount.textContent = parseInt(itemCount.textContent) + 1;

                // Fókusz vissza
                barcodeInput.focus();
            } else {
                errorText.textContent = data.error || 'Hiba történt a mentés során.';
                errorDiv.classList.remove('hidden');
            }
        } catch (err) {
            errorText.textContent = 'Hálózati hiba. Próbálja újra.';
            errorDiv.classList.remove('hidden');
        }
    });

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Automatikus fókusz a vonalkód mezőre
    barcodeInput.focus();
    document.addEventListener('click', function() {
        setTimeout(() => barcodeInput.focus(), 100);
    });
})();
</script>
