<?php
$invoices = $data['invoices'] ?? [];
$skipped = $data['skipped'] ?? [];
?>

<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-heading font-extrabold text-on-surface tracking-tight mb-1">Számlák előnézet</h1>
            <p class="text-on-surface-variant text-sm"><?= count($invoices) ?> számla feldolgozva — jelölje ki melyiket szeretné felvenni</p>
        </div>
        <a href="<?= base_url('/invoices/bulk-upload') ?>" class="text-sm text-on-surface-variant hover:text-on-surface font-medium">
            <i class="fa-solid fa-arrow-left mr-1"></i>Új feltöltés
        </a>
    </div>
</div>

<?php if (!empty($skipped)): ?>
<div class="bg-amber-50 border border-amber-200 rounded-xl p-3 mb-4 text-xs text-amber-700">
    <i class="fa-solid fa-triangle-exclamation mr-1"></i>
    <b>Kihagyva (<?= count($skipped) ?>):</b> <?= implode(' | ', array_map(fn($s) => htmlspecialchars($s), $skipped)) ?>
</div>
<?php endif; ?>

<form method="POST" action="<?= base_url('/invoices/bulk-upload/confirm') ?>">
    <?= csrf_field() ?>

    <!-- Eszközsáv -->
    <div class="bg-surface-container-lowest rounded-xl p-3 mb-4 flex flex-wrap items-center gap-3">
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" id="select-all" class="h-4 w-4 text-primary border-outline rounded focus:ring-primary-container">
            <span class="text-sm font-bold text-on-surface">Összes kijelölése</span>
        </label>
        <span class="text-xs text-on-surface-variant" id="selected-count">0 kijelölve</span>
        <div class="flex-1"></div>
        <button type="submit" id="save-btn" disabled
                class="px-5 py-2 bg-gradient-to-br from-primary to-primary-container text-on-primary-fixed font-bold rounded-full text-sm shadow-lg disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-2">
            <i class="fa-solid fa-check"></i> Kijelöltek felvétele
        </button>
    </div>

    <!-- Táblázat -->
    <div class="bg-surface-container-lowest rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-10"></th>
                    <th>Beszállító</th>
                    <th>Számla szám</th>
                    <th>Dátum</th>
                    <th class="text-right">Nettó</th>
                    <th class="text-right">Bruttó</th>
                    <th>Pénznem</th>
                    <th>Státusz</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $i => $inv): ?>
                <tr class="<?= $inv['duplicate'] ? 'opacity-40' : '' ?>">
                    <td>
                        <input type="checkbox" name="selected[]" value="<?= $i ?>"
                               class="row-check h-4 w-4 text-primary border-outline rounded focus:ring-primary-container"
                               <?= $inv['duplicate'] ? 'disabled' : '' ?>>
                    </td>
                    <td class="text-sm font-medium"><?= e($inv['supplier']) ?></td>
                    <td class="text-xs text-on-surface-variant"><?= e($inv['invoice_number']) ?></td>
                    <td class="text-sm"><?= e($inv['date']) ?></td>
                    <td class="text-right text-sm"><?= number_format($inv['net_amount'], 0, ',', ' ') ?></td>
                    <td class="text-right text-sm font-bold"><?= number_format($inv['amount'], 0, ',', ' ') ?></td>
                    <td class="text-xs"><?= e($inv['currency']) ?></td>
                    <td>
                        <?php if ($inv['duplicate']): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700">
                                <i class="fa-solid fa-triangle-exclamation mr-0.5"></i>Duplikátum
                            </span>
                        <?php elseif ($inv['amount'] > 0): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">
                                <i class="fa-solid fa-check mr-0.5"></i>OK
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700">
                                <i class="fa-solid fa-xmark mr-0.5"></i>Nincs összeg
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- Alsó submit -->
    <div class="mt-4 flex justify-end">
        <button type="submit" id="save-btn-bottom" disabled
                class="px-6 py-3 bg-gradient-to-br from-primary to-primary-container text-on-primary-fixed font-bold rounded-full text-sm shadow-lg disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-2">
            <i class="fa-solid fa-check"></i> Kijelöltek felvétele
        </button>
    </div>
</form>

<script>
const selectAll = document.getElementById('select-all');
const rowChecks = document.querySelectorAll('.row-check:not(:disabled)');
const btns = [document.getElementById('save-btn'), document.getElementById('save-btn-bottom')];
const countEl = document.getElementById('selected-count');

function updateCount() {
    const checked = document.querySelectorAll('.row-check:checked').length;
    countEl.textContent = checked + ' kijelölve';
    btns.forEach(b => { b.disabled = checked === 0; });
}

selectAll.addEventListener('change', function() {
    rowChecks.forEach(cb => { cb.checked = this.checked; });
    updateCount();
});

rowChecks.forEach(cb => cb.addEventListener('change', updateCount));
</script>
