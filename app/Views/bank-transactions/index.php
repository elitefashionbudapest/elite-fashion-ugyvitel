<?php
use App\Models\BankTransaction;
$transactions = $data['transactions'] ?? [];
$banks = $data['banks'] ?? [];
$filters = $data['filters'] ?? [];
?>

<div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-6">
    <div>
        <h1 class="text-3xl font-heading font-extrabold text-on-surface tracking-tight mb-1">Bank tranzakciók</h1>
        <p class="text-on-surface-variant text-sm">Kártyás beérkezések, szolgáltatói levonások, hitel törlesztések.</p>
    </div>
    <div class="flex flex-wrap gap-1.5 sm:gap-2">
        <a href="<?= base_url('/bank-transactions/card/create') ?>" class="px-3 sm:px-5 py-2 sm:py-2.5 bg-gradient-to-br from-primary to-primary-container text-on-primary-fixed font-bold rounded-full flex items-center gap-1.5 shadow-lg shadow-primary/10 text-xs sm:text-sm">
            <i class="fa-solid fa-credit-card"></i> <span class="hidden sm:inline">Kártyás</span> <span class="sm:hidden">Kártya</span>
        </a>
        <a href="<?= base_url('/bank-transactions/provider/create') ?>" class="px-3 sm:px-5 py-2 sm:py-2.5 bg-surface-container-low text-on-surface font-bold rounded-full flex items-center gap-1.5 text-xs sm:text-sm border border-outline-variant">
            <i class="fa-solid fa-building"></i> Szolgáltató
        </a>
        <a href="<?= base_url('/bank-transactions/loan/create') ?>" class="px-3 sm:px-5 py-2 sm:py-2.5 bg-surface-container-low text-on-surface font-bold rounded-full flex items-center gap-1.5 text-xs sm:text-sm border border-outline-variant">
            <i class="fa-solid fa-landmark"></i> Törlesztés
        </a>
        <a href="<?= base_url('/bank-transactions/transfer/create') ?>" class="px-3 sm:px-5 py-2 sm:py-2.5 bg-surface-container-low text-on-surface font-bold rounded-full flex items-center gap-1.5 text-xs sm:text-sm border border-outline-variant">
            <i class="fa-solid fa-arrow-right-arrow-left"></i> Átutalás
        </a>
        <a href="<?= base_url('/bank-transactions/commission/create') ?>" class="px-3 sm:px-5 py-2 sm:py-2.5 bg-surface-container-low text-on-surface font-bold rounded-full flex items-center gap-1.5 text-xs sm:text-sm border border-outline-variant">
            <i class="fa-solid fa-percent"></i> <span class="hidden sm:inline">Banki jutalék</span> <span class="sm:hidden">Jutalék</span>
        </a>
        <a href="<?= base_url('/bank-transactions/tax/create') ?>" class="px-3 sm:px-5 py-2 sm:py-2.5 bg-surface-container-low text-on-surface font-bold rounded-full flex items-center gap-1.5 text-xs sm:text-sm border border-outline-variant">
            <i class="fa-solid fa-file-invoice-dollar"></i> <span class="hidden sm:inline">Adó kifizetés</span> <span class="sm:hidden">Adó</span>
        </a>
        <a href="<?= base_url('/bank-transactions/owner-loan/create') ?>" class="px-3 sm:px-5 py-2 sm:py-2.5 bg-surface-container-low text-on-surface font-bold rounded-full flex items-center gap-1.5 text-xs sm:text-sm border border-outline-variant">
            <i class="fa-solid fa-handshake"></i> <span class="hidden sm:inline">Tagi kölcsön</span> <span class="sm:hidden">Kölcsön</span>
        </a>
        <a href="<?= base_url('/bank-transactions/import') ?>" class="px-3 sm:px-5 py-2 sm:py-2.5 bg-cyan-600 text-white font-bold rounded-full flex items-center gap-1.5 text-xs sm:text-sm shadow-lg shadow-cyan-600/10">
            <i class="fa-solid fa-file-import"></i> <span class="hidden sm:inline">Kivonat import</span> <span class="sm:hidden">Import</span>
        </a>
        <a href="<?= base_url('/bank-transactions/export?' . http_build_query(array_filter($filters))) ?>" class="px-3 sm:px-5 py-2 sm:py-2.5 bg-surface-container-low text-on-surface font-bold rounded-full flex items-center gap-1.5 text-xs sm:text-sm border border-outline-variant">
            <i class="fa-solid fa-file-arrow-down"></i> <span class="hidden sm:inline">CSV Export</span> <span class="sm:hidden">Export</span>
        </a>
        <a href="<?= base_url('/bank-transactions/reconcile') ?>" class="px-3 sm:px-5 py-2 sm:py-2.5 bg-violet-600 text-white font-bold rounded-full flex items-center gap-1.5 text-xs sm:text-sm shadow-lg shadow-violet-600/10">
            <i class="fa-solid fa-scale-balanced"></i> <span class="hidden sm:inline">Egyenleg összevetés</span> <span class="sm:hidden">Összevetés</span>
        </a>
    </div>
</div>

<!-- Szűrők -->
<div class="bg-surface-container-lowest rounded-xl p-2 sm:p-4 mb-4 sm:mb-6">
    <form method="GET" action="<?= base_url('/bank-transactions') ?>" class="flex flex-wrap items-end gap-2 sm:gap-3">
        <div>
            <label class="block text-xs font-bold text-on-surface-variant uppercase mb-1">Bank</label>
            <select name="bank_id" class="px-3 py-1.5 border border-outline-variant rounded-lg text-sm">
                <option value="">Mind</option>
                <?php foreach ($banks as $b): ?>
                    <option value="<?= $b['id'] ?>" <?= ($filters['bank_id'] ?? '') == $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold text-on-surface-variant uppercase mb-1">Típus</label>
            <select name="type" class="px-3 py-1.5 border border-outline-variant rounded-lg text-sm">
                <option value="">Mind</option>
                <option value="kartya_beerkezes" <?= ($filters['type'] ?? '') === 'kartya_beerkezes' ? 'selected' : '' ?>>Kártyás beérkezés</option>
                <option value="szolgaltato_levon" <?= ($filters['type'] ?? '') === 'szolgaltato_levon' ? 'selected' : '' ?>>Szolgáltatói levonás</option>
                <option value="hitel_torlesztes" <?= ($filters['type'] ?? '') === 'hitel_torlesztes' ? 'selected' : '' ?>>Hitel törlesztés</option>
                <option value="szamla_kozti" <?= ($filters['type'] ?? '') === 'szamla_kozti' ? 'selected' : '' ?>>Számlák közötti</option>
                <option value="banki_jutalek" <?= ($filters['type'] ?? '') === 'banki_jutalek' ? 'selected' : '' ?>>Banki jutalék</option>
                <option value="tulajdonosi_fizetes" <?= ($filters['type'] ?? '') === 'tulajdonosi_fizetes' ? 'selected' : '' ?>>Tulajdonosi fizetés</option>
                <option value="ado_kifizetes" <?= ($filters['type'] ?? '') === 'ado_kifizetes' ? 'selected' : '' ?>>Adó kifizetés</option>
                <option value="tagi_kolcson_be" <?= ($filters['type'] ?? '') === 'tagi_kolcson_be' ? 'selected' : '' ?>>Tagi kölcsön be</option>
                <option value="tagi_kolcson_ki" <?= ($filters['type'] ?? '') === 'tagi_kolcson_ki' ? 'selected' : '' ?>>Tagi kölcsön ki</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold text-on-surface-variant uppercase mb-1">Időszak</label>
            <div class="flex gap-1">
                <input type="date" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>" placeholder="Mettől" class="px-3 py-1.5 border border-outline-variant rounded-lg text-sm">
                <input type="date" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>" placeholder="Meddig" class="px-3 py-1.5 border border-outline-variant rounded-lg text-sm">
            </div>
        </div>
        <button type="submit" class="px-4 py-1.5 bg-sidebar text-primary rounded-lg text-sm font-bold hover:bg-gray-800 transition-colors">Szűrés</button>
    </form>
</div>

<!-- Táblázat -->
<!-- Tömeges törlés sáv -->
<div id="bulk-bar" class="hidden bg-red-50 border border-red-200 rounded-xl p-3 mb-3 flex items-center justify-between">
    <span class="text-sm font-bold text-red-700"><i class="fa-solid fa-trash-can mr-1"></i><span id="bulk-count">0</span> kijelölve</span>
    <button type="button" onclick="bulkDelete()" class="px-4 py-2 bg-red-600 text-white rounded-full text-xs font-bold hover:bg-red-700 transition-colors">
        <i class="fa-solid fa-trash mr-1"></i> Kijelöltek törlése
    </button>
</div>

<div class="bg-surface-container-lowest rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
    <table class="data-table" id="tx-table">
        <thead>
            <tr>
                <th class="w-8"><input type="checkbox" id="tx-select-all" class="h-4 w-4 text-primary border-outline rounded"></th>
                <th>Dátum</th>
                <th>Típus</th>
                <th>Bank</th>
                <th>Részletek</th>
                <th class="text-right hide-mobile">Bruttó</th>
                <th class="text-right">Nettó (beérkezett)</th>
                <th class="text-right hide-mobile">Jutalék/Levonás</th>
                <th class="hide-mobile">Számla</th>
                <th class="text-right">Műveletek</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($transactions)): ?>
                <tr><td colspan="10" class="text-center text-on-surface-variant py-8">Nincs bank tranzakció.</td></tr>
            <?php else: ?>
                <?php foreach ($transactions as $tx): ?>
                <tr>
                    <td><input type="checkbox" class="tx-check h-4 w-4 text-primary border-outline rounded" value="<?= $tx['id'] ?>"></td>
                    <td class="text-sm"><?= e($tx['transaction_date']) ?></td>
                    <td>
                        <?php if ($tx['type'] === 'kartya_beerkezes'): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-700">
                                <i class="fa-solid fa-credit-card mr-1"></i>Kártyás
                            </span>
                        <?php elseif ($tx['type'] === 'hitel_torlesztes'): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700">
                                <i class="fa-solid fa-landmark mr-1"></i>Törlesztés
                            </span>
                        <?php elseif ($tx['type'] === 'szamla_kozti'): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-700">
                                <i class="fa-solid fa-arrow-right-arrow-left mr-1"></i>Átutalás
                            </span>
                        <?php elseif ($tx['type'] === 'banki_jutalek'): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">
                                <i class="fa-solid fa-percent mr-1"></i>Jutalék
                            </span>
                        <?php elseif ($tx['type'] === 'tulajdonosi_fizetes'): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-700">
                                <i class="fa-solid fa-user-tie mr-1"></i>Tul. fizetés
                            </span>
                        <?php elseif ($tx['type'] === 'ado_kifizetes'): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-yellow-100 text-yellow-700">
                                <i class="fa-solid fa-file-invoice-dollar mr-1"></i>Adó
                            </span>
                        <?php elseif ($tx['type'] === 'tagi_kolcson_be'): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">
                                <i class="fa-solid fa-handshake mr-1"></i>Kölcsön be
                            </span>
                        <?php elseif ($tx['type'] === 'tagi_kolcson_ki'): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-700">
                                <i class="fa-solid fa-handshake mr-1"></i>Kölcsön ki
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-orange-100 text-orange-700">
                                <i class="fa-solid fa-building mr-1"></i>Szolgáltató
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="text-sm"><?= e($tx['bank_name']) ?></td>
                    <td class="text-sm">
                        <?php if ($tx['type'] === 'kartya_beerkezes'): ?>
                            <div class="flex flex-wrap gap-1">
                                <?php foreach ($tx['stores'] ?? [] as $s): ?>
                                    <span class="px-2 py-0.5 bg-surface-container text-on-surface text-[10px] font-bold rounded-full"><?= e($s['store_name']) ?></span>
                                <?php endforeach; ?>
                            </div>
                            <span class="text-xs text-on-surface-variant"><?= e($tx['date_from']) ?> — <?= e($tx['date_to']) ?></span>
                        <?php elseif ($tx['type'] === 'banki_jutalek'): ?>
                            <span class="font-medium"><?= e($tx['notes'] ?? 'Banki jutalék') ?></span>
                        <?php elseif ($tx['type'] === 'ado_kifizetes'): ?>
                            <span class="font-medium"><?= e($tx['notes'] ?? 'Adó kifizetés') ?></span>
                        <?php elseif ($tx['type'] === 'tulajdonosi_fizetes'): ?>
                            <span class="font-medium"><?= e($tx['notes'] ?? 'Tulajdonosi fizetés') ?></span>
                        <?php elseif ($tx['type'] === 'tagi_kolcson_be' || $tx['type'] === 'tagi_kolcson_ki'): ?>
                            <span class="font-medium"><?= e($tx['notes'] ?? 'Tagi kölcsön') ?></span>
                        <?php elseif ($tx['type'] === 'hitel_torlesztes'): ?>
                            <span class="font-medium"><?= e($tx['loan_name'] ?? '') ?></span>
                        <?php elseif ($tx['type'] === 'szamla_kozti'): ?>
                            <span class="font-medium">→ <?= e($tx['target_bank_name'] ?? '') ?></span>
                            <?php if ($tx['source_amount'] && $tx['source_amount'] != $tx['amount']): ?>
                                <span class="text-xs text-on-surface-variant block"><?= format_money($tx['source_amount']) ?> → <?= number_format($tx['amount'], 2, ',', ' ') ?> <?= e($tx['target_currency'] ?? '') ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="font-medium"><?= e($tx['provider_name']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right text-sm hide-mobile">
                        <?php if ($tx['type'] === 'kartya_beerkezes'): ?>
                            <?= format_money($tx['gross_amount'] ?? 0) ?>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <?php $isIncoming = in_array($tx['type'], ['kartya_beerkezes', 'tagi_kolcson_be']); ?>
                    <td class="text-right font-medium <?= $isIncoming ? 'text-emerald-600' : 'text-red-600' ?>">
                        <?= $isIncoming ? '+' : '-' ?><?= format_money($tx['amount']) ?>
                    </td>
                    <td class="text-right text-sm text-red-500 hide-mobile">
                        <?php if ($tx['type'] === 'kartya_beerkezes'): ?>
                            <?= format_money($tx['commission'] ?? 0) ?>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td class="text-sm hide-mobile">
                        <?php if ($tx['type'] === 'szolgaltato_levon'): ?>
                            <?php if ($tx['invoice_id']): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-100 text-green-700">
                                    <i class="fa-solid fa-link mr-0.5"></i><?= e($tx['invoice_number'] ?? 'Összekötve') ?>
                                </span>
                            <?php else: ?>
                                <form method="POST" action="<?= base_url("/bank-transactions/{$tx['id']}/link-invoice") ?>" class="flex items-center gap-1">
                                    <?= csrf_field() ?>
                                    <select name="invoice_id" class="text-xs border border-outline-variant rounded px-1 py-0.5">
                                        <option value="">—</option>
                                        <?php
                                        $db = \App\Core\Database::getInstance();
                                        $invs = $db->query("SELECT i.id, i.invoice_number, sp.name FROM invoices i JOIN suppliers sp ON i.supplier_id = sp.id WHERE i.store_id IS NULL ORDER BY i.invoice_date DESC LIMIT 30")->fetchAll();
                                        foreach ($invs as $inv): ?>
                                            <option value="<?= $inv['id'] ?>"><?= e($inv['name']) ?> — <?= e($inv['invoice_number']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="text-xs text-primary hover:text-primary/80 font-bold"><i class="fa-solid fa-link"></i></button>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td class="text-right">
                        <a href="<?= base_url("/bank-transactions/{$tx['id']}/edit") ?>" class="text-blue-500 hover:text-blue-700 text-sm mr-1"><i class="fa-solid fa-pen-to-square"></i></a>
                        <form method="POST" action="<?= base_url("/bank-transactions/{$tx['id']}/delete") ?>" class="inline" onsubmit="return confirmDelete(this, 'tranzakció')">
                            <?= csrf_field() ?>
                            <button type="submit" class="text-red-500 hover:text-red-700 text-sm"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<script>
(function() {
    var selectAll = document.getElementById('tx-select-all');
    var checks = document.querySelectorAll('.tx-check');
    var bulkBar = document.getElementById('bulk-bar');
    var bulkCount = document.getElementById('bulk-count');

    function updateBulk() {
        var count = document.querySelectorAll('.tx-check:checked').length;
        bulkCount.textContent = count;
        bulkBar.classList.toggle('hidden', count === 0);
    }

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checks.forEach(function(cb) { cb.checked = selectAll.checked; });
            updateBulk();
        });
    }

    checks.forEach(function(cb) { cb.addEventListener('change', updateBulk); });

    window.bulkDelete = async function() {
        var ids = Array.from(document.querySelectorAll('.tx-check:checked')).map(function(cb) { return cb.value; });
        if (ids.length === 0) return;
        if (!confirm('Biztosan törölni szeretnéd a kijelölt ' + ids.length + ' tranzakciót? Ez nem vonható vissza!')) return;

        try {
            var resp = await fetch('<?= base_url('/bank-transactions/bulk-delete') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
                body: JSON.stringify({ ids: ids })
            });
            var data = await resp.json();
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Hiba történt a törlés során.');
            }
        } catch(e) {
            alert('Hálózati hiba.');
        }
    };
})();
</script>
