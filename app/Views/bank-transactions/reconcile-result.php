<?php
use App\Models\BankTransaction;
$bank            = $data['bank'];
$dateFrom        = $data['dateFrom'];
$dateTo          = $data['dateTo'];
$openingBalance  = $data['openingBalance'];
$csvBalance      = $data['csvBalance'];
$systemBalance   = $data['systemBalance'];
$csvTotal        = $data['csvTotal'];
$systemTotal     = $data['systemTotal'];
$unmatchedCsv    = $data['unmatchedCsv'];
$unmatchedSystem = $data['unmatchedSystem'];
$csvRowCount     = $data['csvRowCount'];
$matchedCount    = $data['matchedCount'];

$diff = $csvBalance - $systemBalance;
$isOk = abs($diff) < 1;
$typeLabels = BankTransaction::TYPES;
?>

<div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl sm:text-3xl font-heading font-extrabold text-on-surface tracking-tight mb-1">Egyenleg összevetés</h1>
        <p class="text-on-surface-variant text-sm">
            <i class="fa-solid fa-building-columns mr-1"></i><?= e($bank['name']) ?> — <?= e($dateFrom) ?> – <?= e($dateTo) ?>
        </p>
    </div>
    <a href="<?= base_url('/bank-transactions/reconcile') ?>" class="px-4 py-2 bg-violet-600 text-white font-bold rounded-full text-xs hover:bg-violet-700 transition-colors">
        <i class="fa-solid fa-rotate mr-1"></i>Új összevetés
    </a>
</div>

<!-- Összefoglaló kártyák -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-surface-container-lowest rounded-xl p-5 text-center">
        <p class="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest mb-1">Nyitó egyenleg</p>
        <p class="text-xl font-heading font-bold text-on-surface"><?= number_format($openingBalance, 0, ',', ' ') ?> Ft</p>
    </div>
    <div class="bg-surface-container-lowest rounded-xl p-5 text-center">
        <p class="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest mb-1">Banki kivonat szerinti</p>
        <p class="text-xl font-heading font-bold text-on-surface"><?= number_format($csvBalance, 0, ',', ' ') ?> Ft</p>
        <p class="text-[10px] text-on-surface-variant mt-1">
            <span class="text-emerald-600">+<?= number_format($csvTotal['in'], 0, ',', ' ') ?></span>
            <span class="text-red-500 ml-1">-<?= number_format($csvTotal['out'], 0, ',', ' ') ?></span>
        </p>
    </div>
    <div class="bg-surface-container-lowest rounded-xl p-5 text-center">
        <p class="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest mb-1">Rendszer szerinti</p>
        <p class="text-xl font-heading font-bold text-on-surface"><?= number_format($systemBalance, 0, ',', ' ') ?> Ft</p>
        <p class="text-[10px] text-on-surface-variant mt-1">
            <span class="text-emerald-600">+<?= number_format($systemTotal['in'], 0, ',', ' ') ?></span>
            <span class="text-red-500 ml-1">-<?= number_format($systemTotal['out'], 0, ',', ' ') ?></span>
        </p>
    </div>
    <div class="rounded-xl p-5 text-center <?= $isOk ? 'bg-emerald-50 border-2 border-emerald-300' : 'bg-red-50 border-2 border-red-300' ?>">
        <p class="text-[10px] font-bold uppercase tracking-widest mb-1 <?= $isOk ? 'text-emerald-600' : 'text-red-600' ?>">Eltérés</p>
        <p class="text-xl font-heading font-bold <?= $isOk ? 'text-emerald-700' : 'text-red-700' ?>">
            <?php if ($isOk): ?>
                <i class="fa-solid fa-circle-check mr-1"></i>Egyezik!
            <?php else: ?>
                <?= $diff > 0 ? '+' : '' ?><?= number_format($diff, 0, ',', ' ') ?> Ft
            <?php endif; ?>
        </p>
    </div>
</div>

<!-- Párosítási statisztika -->
<div class="bg-surface-container-lowest rounded-xl p-4 mb-6">
    <div class="flex flex-wrap gap-6 items-center">
        <div>
            <span class="text-xs text-on-surface-variant">Kivonat sorai:</span>
            <span class="font-bold text-on-surface ml-1"><?= $csvRowCount ?></span>
        </div>
        <div>
            <span class="text-xs text-on-surface-variant">Párosítva:</span>
            <span class="font-bold text-emerald-600 ml-1"><?= $matchedCount ?></span>
        </div>
        <div>
            <span class="text-xs text-on-surface-variant">Kivonatban van, rendszerben nincs:</span>
            <span class="font-bold <?= count($unmatchedCsv) > 0 ? 'text-red-600' : 'text-emerald-600' ?> ml-1"><?= count($unmatchedCsv) ?></span>
        </div>
        <div>
            <span class="text-xs text-on-surface-variant">Rendszerben van, kivonatban nincs:</span>
            <span class="font-bold <?= count($unmatchedSystem) > 0 ? 'text-amber-600' : 'text-emerald-600' ?> ml-1"><?= count($unmatchedSystem) ?></span>
        </div>
    </div>
</div>

<?php if (!empty($unmatchedCsv)): ?>
<!-- Kivonatban van, rendszerben NINCS -->
<div class="bg-surface-container-lowest rounded-xl overflow-hidden mb-6">
    <div class="px-5 py-4 border-b border-surface-container flex items-center gap-2">
        <i class="fa-solid fa-triangle-exclamation text-red-500"></i>
        <h3 class="font-heading font-bold text-on-surface">Banki kivonatban van, rendszerben NINCS (<?= count($unmatchedCsv) ?>)</h3>
    </div>
    <div class="overflow-x-auto">
    <table class="data-table">
        <thead>
            <tr>
                <th>Dátum</th>
                <th>Irány</th>
                <th class="text-right">Összeg</th>
                <th>Partner</th>
                <th>Leírás</th>
                <th>Típus (bank)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($unmatchedCsv as $row): ?>
            <tr class="bg-red-50/30">
                <td class="text-sm"><?= e($row['booking_date']) ?></td>
                <td>
                    <?php if ($row['direction'] === 'J'): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">BE</span>
                    <?php else: ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700">KI</span>
                    <?php endif; ?>
                </td>
                <td class="text-right font-bold text-sm <?= $row['direction'] === 'J' ? 'text-emerald-600' : 'text-red-600' ?>">
                    <?= $row['direction'] === 'J' ? '+' : '-' ?><?= number_format($row['amount'], 0, ',', ' ') ?> Ft
                </td>
                <td class="text-sm"><?= e($row['partner_name']) ?></td>
                <td class="text-sm text-on-surface-variant max-w-[200px] truncate"><?= e($row['description']) ?></td>
                <td class="text-xs text-on-surface-variant"><?= e($row['csv_type']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($unmatchedSystem)): ?>
<!-- Rendszerben van, kivonatban NINCS -->
<div class="bg-surface-container-lowest rounded-xl overflow-hidden mb-6">
    <div class="px-5 py-4 border-b border-surface-container flex items-center gap-2">
        <i class="fa-solid fa-circle-question text-amber-500"></i>
        <h3 class="font-heading font-bold text-on-surface">Rendszerben van, banki kivonatban NINCS (<?= count($unmatchedSystem) ?>)</h3>
    </div>
    <div class="overflow-x-auto">
    <table class="data-table">
        <thead>
            <tr>
                <th>Dátum</th>
                <th>Típus</th>
                <th class="text-right">Összeg</th>
                <th>Részletek</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($unmatchedSystem as $tx): ?>
            <?php $isIncoming = in_array($tx['type'], ['kartya_beerkezes', 'tagi_kolcson_be']); ?>
            <tr class="bg-amber-50/30">
                <td class="text-sm"><?= e($tx['transaction_date']) ?></td>
                <td class="text-sm"><?= e($typeLabels[$tx['type']] ?? $tx['type']) ?></td>
                <td class="text-right font-bold text-sm <?= $isIncoming ? 'text-emerald-600' : 'text-red-600' ?>">
                    <?= $isIncoming ? '+' : '-' ?><?= number_format($tx['amount'], 0, ',', ' ') ?> Ft
                </td>
                <td class="text-sm text-on-surface-variant"><?= e($tx['notes'] ?? $tx['provider_name'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endif; ?>

<?php if ($isOk && empty($unmatchedCsv) && empty($unmatchedSystem)): ?>
<div class="bg-emerald-50 border-2 border-emerald-300 rounded-xl p-8 text-center">
    <i class="fa-solid fa-circle-check text-emerald-500 text-4xl mb-3"></i>
    <h3 class="font-heading font-bold text-xl text-emerald-700 mb-1">Minden egyezik!</h3>
    <p class="text-sm text-emerald-600">A banki kivonat és a rendszer tételei teljesen megegyeznek.</p>
</div>
<?php endif; ?>
