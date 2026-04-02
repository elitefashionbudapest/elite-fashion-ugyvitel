<?php
use App\Models\BankTransaction;
$rows = $data['rows'] ?? [];
$bankId = $data['bank_id'] ?? 0;
$bankName = $data['bank_name'] ?? '';
$stores = $data['stores'] ?? [];
$typeLabels = BankTransaction::TYPES;

$typeIcons = [
    'kartya_beerkezes' => '💳',
    'szolgaltato_levon' => '🏢',
    'hitel_torlesztes' => '🏦',
    'szamla_kozti' => '↔️',
    'banki_jutalek' => '📊',
    'tulajdonosi_fizetes' => '👔',
    'ado_kifizetes' => '📄',
    'tagi_kolcson_be' => '🤝↓',
    'tagi_kolcson_ki' => '🤝↑',
];
?>

<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-heading font-extrabold text-on-surface tracking-tight mb-1">Kivonat előnézet</h1>
            <p class="text-on-surface-variant text-sm">
                <i class="fa-solid fa-building-columns mr-1"></i><?= e($bankName) ?> — <?= count($rows) ?> sor találva
            </p>
        </div>
        <a href="<?= base_url('/bank-transactions/import') ?>" class="text-sm text-on-surface-variant hover:text-on-surface font-medium">
            <i class="fa-solid fa-arrow-left mr-1"></i>Vissza a feltöltéshez
        </a>
    </div>
</div>

<form method="POST" action="<?= base_url('/bank-transactions/import/store') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="bank_id" value="<?= $bankId ?>">

    <!-- Eszközsáv -->
    <div class="bg-surface-container-lowest rounded-xl p-3 mb-4 flex flex-wrap items-center gap-3">
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" id="select-all" class="h-4 w-4 text-primary border-outline rounded focus:ring-primary-container">
            <span class="text-sm font-bold text-on-surface">Összes kijelölése</span>
        </label>
        <span class="text-xs text-on-surface-variant" id="selected-count">0 kijelölve</span>
        <div class="flex-1"></div>
        <button type="submit" id="import-btn" disabled
                class="px-5 py-2 bg-gradient-to-br from-primary to-primary-container text-on-primary-fixed font-bold rounded-full text-sm shadow-lg shadow-primary/10 hover:shadow-primary/20 transition-all flex items-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed">
            <i class="fa-solid fa-check"></i> Kijelöltek importálása
        </button>
    </div>

    <!-- Táblázat -->
    <div class="bg-surface-container-lowest rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-10"></th>
                    <th>Dátum</th>
                    <th>Irány</th>
                    <th class="text-right">Összeg</th>
                    <th>Partner / Leírás</th>
                    <th>Bank típus</th>
                    <th>Művelet típusa</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $i => $row): ?>
                <tr class="import-row <?= $row['duplicate'] ? 'opacity-50' : '' ?>">
                    <td>
                        <input type="checkbox" name="selected[]" value="<?= $i ?>"
                               class="row-check h-4 w-4 text-primary border-outline rounded focus:ring-primary-container">
                    </td>
                    <td class="text-sm"><?= e($row['booking_date']) ?></td>
                    <td>
                        <?php if ($row['direction'] === 'J'): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">BE</span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700">KI</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right text-sm">
                        <div class="font-bold <?= $row['direction'] === 'J' ? 'text-emerald-600' : 'text-red-600' ?>">
                            <?= $row['direction'] === 'J' ? '+' : '-' ?><?= number_format($row['amount'], 0, ',', ' ') ?> Ft
                        </div>
                        <?php if (!empty($row['brutto'])): ?>
                            <div class="text-[10px] text-on-surface-variant">Bruttó: <?= number_format($row['brutto'], 0, ',', ' ') ?> Ft</div>
                            <div class="text-[10px] text-red-400">Jutalék: <?= number_format($row['jutalek'] ?? 0, 2, ',', ' ') ?> Ft</div>
                        <?php endif; ?>
                    </td>
                    <td class="text-sm max-w-[200px]">
                        <?php if ($row['partner_name']): ?>
                            <div class="font-medium truncate"><?= e($row['partner_name']) ?></div>
                        <?php endif; ?>
                        <?php if ($row['description']): ?>
                            <div class="text-[10px] text-on-surface-variant truncate"><?= e($row['description']) ?></div>
                        <?php endif; ?>
                        <?php if ($row['duplicate']): ?>
                            <span class="text-[10px] text-amber-600 font-bold"><i class="fa-solid fa-triangle-exclamation mr-0.5"></i>Lehet duplikált</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-xs text-on-surface-variant"><?= e($row['csv_type']) ?></td>
                    <td>
                        <select name="types[<?= $i ?>]" class="type-select px-2 py-1 border border-outline-variant rounded-lg text-xs bg-surface-container-lowest focus:ring-1 focus:ring-primary" data-row="<?= $i ?>" onchange="toggleStores(this)">
                            <option value="">-- válassz --</option>
                            <?php foreach ($typeLabels as $key => $label): ?>
                                <option value="<?= $key ?>" <?= ($row['suggested_type'] ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php $showStores = in_array($row['suggested_type'] ?? '', ['kartya_beerkezes', 'befizetes_boltbol']); ?>
                        <div id="stores-<?= $i ?>" class="mt-1 <?= $showStores ? '' : 'hidden' ?>" data-date="<?= e($row['booking_date']) ?>">
                            <div id="lookup-date-wrap-<?= $i ?>" class="mb-1 <?= in_array($row['suggested_type'] ?? '', ['befizetes_boltbol', 'kartya_beerkezes']) ? '' : 'hidden' ?>">
                                <div class="flex flex-wrap items-center gap-1">
                                    <label class="text-[10px] text-on-surface-variant">Időszak:</label>
                                    <input type="date" name="date_from[<?= $i ?>]" id="lookup-date-from-<?= $i ?>" value="<?= e(date('Y-m-d', strtotime($row['booking_date'] . ' -1 day'))) ?>"
                                           class="px-1.5 py-0.5 border border-outline-variant rounded text-[11px]"
                                           onchange="fetchGross(<?= $i ?>)">
                                    <span class="text-[10px] text-on-surface-variant">—</span>
                                    <input type="date" name="date_to[<?= $i ?>]" id="lookup-date-to-<?= $i ?>" value="<?= e(date('Y-m-d', strtotime($row['booking_date'] . ' -1 day'))) ?>"
                                           class="px-1.5 py-0.5 border border-outline-variant rounded text-[11px]"
                                           onchange="fetchGross(<?= $i ?>)">
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-1">
                            <?php foreach ($stores as $s): ?>
                            <label class="inline-flex items-center gap-1 cursor-pointer px-2 py-0.5 rounded text-[10px] border border-surface-container hover:border-primary has-[:checked]:border-primary has-[:checked]:bg-primary-container/20">
                                <input type="checkbox" name="store_ids[<?= $i ?>][]" value="<?= $s['id'] ?>"
                                       class="h-3 w-3 text-primary border-outline rounded"
                                       onchange="fetchGross(<?= $i ?>)">
                                <span><?= e($s['name']) ?></span>
                            </label>
                            <?php endforeach; ?>
                            </div>
                            <div id="gross-info-<?= $i ?>" class="text-[10px] mt-1"></div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- Alsó submit -->
    <div class="mt-4 flex justify-end">
        <button type="submit" id="import-btn-bottom" disabled
                class="px-6 py-3 bg-gradient-to-br from-primary to-primary-container text-on-primary-fixed font-bold rounded-full text-sm shadow-lg shadow-primary/10 flex items-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed">
            <i class="fa-solid fa-check"></i> Kijelöltek importálása
        </button>
    </div>
</form>

<script>
const selectAll = document.getElementById('select-all');
const rowChecks = document.querySelectorAll('.row-check');
const importBtns = [document.getElementById('import-btn'), document.getElementById('import-btn-bottom')];
const countEl = document.getElementById('selected-count');

function updateCount() {
    const checked = document.querySelectorAll('.row-check:checked').length;
    countEl.textContent = checked + ' kijelölve';
    importBtns.forEach(btn => {
        btn.disabled = checked === 0;
    });
}

selectAll.addEventListener('change', function() {
    rowChecks.forEach(cb => { cb.checked = this.checked; });
    updateCount();
});

rowChecks.forEach(cb => {
    cb.addEventListener('change', updateCount);
});

function toggleStores(select) {
    const row = select.dataset.row;
    const storesDiv = document.getElementById('stores-' + row);
    const dateWrap = document.getElementById('lookup-date-wrap-' + row);
    const needsStores = ['kartya_beerkezes', 'befizetes_boltbol'];
    if (needsStores.includes(select.value)) {
        storesDiv.classList.remove('hidden');
    } else {
        storesDiv.classList.add('hidden');
    }
    if (needsStores.includes(select.value)) {
        dateWrap.classList.remove('hidden');
    } else {
        dateWrap.classList.add('hidden');
    }
    fetchGross(parseInt(row));
}

async function fetchGross(rowIdx) {
    const storesDiv = document.getElementById('stores-' + rowIdx);
    const infoDiv = document.getElementById('gross-info-' + rowIdx);
    const date = storesDiv.dataset.date;
    const checked = storesDiv.querySelectorAll('input[type=checkbox]:checked');
    const typeSelect = storesDiv.closest('td').querySelector('.type-select');
    const type = typeSelect ? typeSelect.value : '';

    if (checked.length === 0 || !date) {
        infoDiv.innerHTML = '';
        return;
    }

    const purpose = (type === 'befizetes_boltbol') ? 'bank_kifizetes' : 'napi_bankkartya';

    // Időszak dátumok (date_from — date_to)
    const dateFromInput = document.getElementById('lookup-date-from-' + rowIdx);
    const dateToInput = document.getElementById('lookup-date-to-' + rowIdx);
    let dateFrom = dateFromInput ? dateFromInput.value : date;
    let dateTo = dateToInput ? dateToInput.value : date;

    const params = new URLSearchParams();
    checked.forEach(cb => params.append('store_ids[]', cb.value));
    params.set('date_from', dateFrom);
    params.set('date_to', dateTo);
    params.set('purpose', purpose);

    try {
        const resp = await fetch('<?= base_url('/bank-transactions/api/gross') ?>?' + params);
        const data = await resp.json();
        const expected = data.gross || 0;

        // Az adott sor összege
        const amountCell = storesDiv.closest('tr').querySelector('td:nth-child(4)');
        const netText = amountCell.querySelector('.font-bold').textContent.replace(/[^\d]/g, '');
        const actual = parseInt(netText) || 0;

        if (type === 'befizetes_boltbol') {
            // Befizetés: könyvelés szerinti összeg vs bank kivonat
            const diff = actual - expected;
            let html = '<span class="text-on-surface-variant">Boltok befizetése: <b>' + new Intl.NumberFormat('hu-HU').format(expected) + ' Ft</b></span>';
            if (expected > 0) {
                if (diff === 0) {
                    html += ' <span class="text-emerald-500">| <i class="fa-solid fa-check"></i> Egyezik</span>';
                } else {
                    html += ' <span class="text-red-500">| Eltérés: <b>' + (diff > 0 ? '+' : '') + new Intl.NumberFormat('hu-HU').format(diff) + ' Ft</b></span>';
                }
            } else if (expected === 0) {
                html = '<span class="text-amber-500"><i class="fa-solid fa-triangle-exclamation mr-0.5"></i>Nincs befizetés rögzítve erre a napra</span>';
            }
            infoDiv.innerHTML = html;
        } else {
            // Kártyás: bruttó és jutalék
            const commission = expected - actual;
            let html = '<span class="text-on-surface-variant">Könyvelés bruttó: <b>' + new Intl.NumberFormat('hu-HU').format(expected) + ' Ft</b></span>';
            if (expected > 0 && commission > 0) {
                const pct = ((commission / expected) * 100).toFixed(2);
                html += ' <span class="text-red-500">| Jutalék: <b>' + new Intl.NumberFormat('hu-HU').format(commission) + ' Ft</b> (' + pct + '%)</span>';
            } else if (expected === 0) {
                html = '<span class="text-amber-500"><i class="fa-solid fa-triangle-exclamation mr-0.5"></i>Nincs könyvelési adat erre a napra</span>';
            }
            infoDiv.innerHTML = html;
        }
    } catch (e) {
        infoDiv.innerHTML = '<span class="text-red-500">Hiba a lekérdezés során</span>';
    }
}
</script>
