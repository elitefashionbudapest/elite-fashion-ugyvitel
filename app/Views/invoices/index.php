<?php
use App\Core\Auth;
use App\Models\Invoice;

$invoices = $data['invoices'] ?? [];
$stores = $data['stores'] ?? [];
$suppliers = $data['suppliers'] ?? [];
$filters = $data['filters'] ?? [];

$overdueCount = 0;
$unpaidTotal = 0;
foreach ($invoices as $inv) {
    if (!$inv['is_paid']) {
        $unpaidTotal += $inv['amount'];
        if ($inv['due_date'] && $inv['due_date'] < date('Y-m-d')) $overdueCount++;
    }
}
?>

<div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8">
    <div>
        <h1 class="text-3xl font-heading font-extrabold text-on-surface tracking-tight mb-1">Bejövő számlák</h1>
        <p class="text-on-surface-variant text-sm">Beszállítói számlák nyilvántartása és követése.</p>
    </div>
    <a href="<?= base_url('/invoices/create') ?>" class="px-6 py-3 bg-gradient-to-br from-primary to-primary-container text-on-primary-fixed font-bold rounded-full flex items-center gap-2 shadow-lg shadow-primary/10 hover:shadow-primary/20 transition-all text-sm">
        <i class="fa-solid fa-plus"></i> Új számla
    </a>
</div>

<!-- Összesítő kártyák -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
    <div class="bg-surface-container-lowest rounded-xl p-4">
        <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest block mb-1">Összes számla</span>
        <span class="text-2xl font-heading font-extrabold text-on-surface"><?= count($invoices) ?></span>
    </div>
    <div class="bg-surface-container-lowest rounded-xl p-4">
        <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest block mb-1">Fizetetlen összeg</span>
        <span class="text-2xl font-heading font-extrabold text-red-600"><?= format_money($unpaidTotal) ?></span>
    </div>
    <?php if ($overdueCount > 0): ?>
    <div class="bg-red-50 rounded-xl p-4 border border-red-200">
        <span class="text-[10px] font-bold text-red-500 uppercase tracking-widest block mb-1">Lejárt határidő!</span>
        <span class="text-2xl font-heading font-extrabold text-red-600"><?= $overdueCount ?> db</span>
    </div>
    <?php endif; ?>
</div>

<!-- Szűrők -->
<div class="bg-surface-container-low p-4 rounded-lg flex flex-wrap items-center gap-4 mb-6">
    <span class="text-xs font-bold text-on-surface-variant uppercase tracking-widest">Szűrők</span>
    <form method="GET" action="<?= base_url('/invoices') ?>" class="flex flex-wrap gap-3 items-center flex-1">
        <?php if (Auth::isOwner()): ?>
        <select name="store_id" class="bg-surface-container-lowest border-none rounded-full text-xs font-semibold py-2 px-4 focus:ring-2 focus:ring-primary-container cursor-pointer">
            <option value="">Minden bolt</option>
            <?php foreach ($stores as $s): ?>
                <option value="<?= $s['id'] ?>" <?= ($filters['store_id'] ?? '') == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <select name="supplier_id" class="bg-surface-container-lowest border-none rounded-full text-xs font-semibold py-2 px-4 focus:ring-2 focus:ring-primary-container cursor-pointer">
            <option value="">Minden beszállító</option>
            <?php foreach ($suppliers as $sp): ?>
                <option value="<?= $sp['id'] ?>" <?= ($filters['supplier_id'] ?? '') == $sp['id'] ? 'selected' : '' ?>><?= e($sp['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="is_paid" class="bg-surface-container-lowest border-none rounded-full text-xs font-semibold py-2 px-4 focus:ring-2 focus:ring-primary-container cursor-pointer">
            <option value="">Mind</option>
            <option value="0" <?= ($filters['is_paid'] ?? '') === '0' ? 'selected' : '' ?>>Fizetetlen</option>
            <option value="1" <?= ($filters['is_paid'] ?? '') === '1' ? 'selected' : '' ?>>Fizetve</option>
        </select>
        <button type="submit" class="px-5 py-2 bg-secondary-container text-on-secondary-container font-semibold rounded-full text-xs hover:bg-surface-variant transition-colors">Szűrés</button>
    </form>
</div>

<!-- Táblázat -->
<div class="bg-surface-container-lowest rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-surface-container-low">
                    <th class="px-2 sm:px-6 py-3 sm:py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Beszállító</th>
                    <th class="px-2 sm:px-4 py-3 sm:py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Számlaszám</th>
                    <th class="px-2 sm:px-4 py-3 sm:py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest text-right">Összeg</th>
                    <th class="px-2 sm:px-4 py-3 sm:py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest hide-mobile">Bolt</th>
                    <th class="px-2 sm:px-4 py-3 sm:py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest hide-mobile">Dátum</th>
                    <th class="px-2 sm:px-4 py-3 sm:py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest hide-mobile">Határidő</th>
                    <th class="px-2 sm:px-4 py-3 sm:py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest hide-mobile">Fiz. mód</th>
                    <th class="px-2 sm:px-4 py-3 sm:py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Státusz</th>
                    <th class="px-2 sm:px-4 py-3 sm:py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest hide-mobile">Kép</th>
                    <th class="px-2 sm:px-6 py-3 sm:py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest text-right">Műv.</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-container">
                <?php if (empty($invoices)): ?>
                    <tr><td colspan="10" class="px-8 py-12 text-center text-on-surface-variant">
                        <i class="fa-solid fa-file-invoice text-4xl mb-2 block text-outline-variant"></i>
                        Nincs találat.
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($invoices as $inv): ?>
                    <?php $isOverdue = !$inv['is_paid'] && $inv['due_date'] && $inv['due_date'] < date('Y-m-d'); ?>
                    <tr class="hover:bg-surface-container-low/50 transition-colors <?= $isOverdue ? 'bg-red-50/30' : '' ?>">
                        <td class="px-2 sm:px-6 py-3 sm:py-4">
                            <div class="flex items-center gap-2 sm:gap-3">
                                <div class="w-7 h-7 sm:w-9 sm:h-9 rounded-lg bg-tertiary-container/30 flex items-center justify-center flex-shrink-0">
                                    <i class="fa-solid fa-truck text-on-tertiary-container text-xs sm:text-sm"></i>
                                </div>
                                <span class="font-bold text-on-surface text-xs sm:text-sm truncate max-w-[80px] sm:max-w-none"><?= e($inv['supplier_name']) ?></span>
                            </div>
                        </td>
                        <td class="px-2 sm:px-4 py-3 sm:py-4 text-xs sm:text-sm font-mono text-on-surface-variant truncate max-w-[100px] sm:max-w-none"><?= e($inv['invoice_number']) ?></td>
                        <td class="px-2 sm:px-4 py-3 sm:py-4 text-right font-bold text-on-surface whitespace-nowrap text-xs sm:text-sm">
                            <?= $inv['currency'] === 'EUR'
                                ? number_format($inv['amount'], 2, ',', ' ') . ' €'
                                : format_money($inv['amount']) ?>
                        </td>
                        <td class="px-2 sm:px-4 py-3 sm:py-4 text-sm text-on-surface-variant hide-mobile"><?= e($inv['store_name']) ?></td>
                        <td class="px-2 sm:px-4 py-3 sm:py-4 text-sm text-on-surface-variant whitespace-nowrap hide-mobile"><?= date('Y.m.d', strtotime($inv['invoice_date'])) ?></td>
                        <td class="px-4 py-4 text-sm whitespace-nowrap hide-mobile <?= $isOverdue ? 'text-red-600 font-bold' : 'text-on-surface-variant' ?>">
                            <?= $inv['due_date'] ? date('Y.m.d', strtotime($inv['due_date'])) : '—' ?>
                            <?php if ($isOverdue): ?><i class="fa-solid fa-triangle-exclamation text-[10px] ml-1"></i><?php endif; ?>
                        </td>
                        <td class="px-4 py-4 hide-mobile">
                            <span class="px-2.5 py-1 bg-surface-container text-on-surface text-[10px] font-bold rounded-full"><?= e(Invoice::PAYMENT_METHODS[$inv['payment_method']] ?? $inv['payment_method']) ?></span>
                        </td>
                        <td class="px-4 py-4">
                            <?php if (!empty($inv['needs_review'])): ?>
                                <div class="text-amber-600 font-semibold text-xs mb-1">
                                    <i class="fa-solid fa-triangle-exclamation text-[10px]"></i> Ellenőrizd!
                                </div>
                            <?php endif; ?>
                            <?php if ($inv['is_paid']): ?>
                                <div class="text-emerald-600 font-semibold text-xs">
                                    <i class="fa-solid fa-circle-check text-[10px]"></i> Fizetve
                                    <?php if (!empty($inv['bank_name'])): ?>
                                        <span class="block text-[10px] text-emerald-500 font-medium mt-0.5"><i class="fa-solid fa-building-columns text-[8px]"></i> <?= e($inv['bank_name']) ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-red-500 font-semibold text-xs">
                                    <i class="fa-regular fa-circle text-[10px]"></i> Fizetetlen
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-4 hide-mobile">
                            <?php if (!empty($inv['image_path'])): ?>
                                <a href="<?= base_url($inv['image_path']) ?>" target="_blank" class="text-blue-500 hover:text-blue-700" title="Számla megtekintése">
                                    <i class="fa-solid fa-file-image"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <?php if (!$inv['is_paid']): ?>
                                <form method="POST" action="<?= base_url("/invoices/{$inv['id']}/paid") ?>" class="flex items-center gap-1">
                                    <?= csrf_field() ?>
                                    <select name="bank_id" class="text-[11px] border border-gray-200 rounded-lg px-2 py-1.5 focus:ring-1 focus:ring-primary-container bg-white" required>
                                        <option value="">Bank...</option>
                                        <?php foreach ($data['banks'] as $bank): ?>
                                            <option value="<?= $bank['id'] ?>"><?= e($bank['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="p-1.5 hover:bg-emerald-100 rounded-lg transition-colors text-emerald-600" title="Átutalva">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                </form>
                                <?php else: ?>
                                <form method="POST" action="<?= base_url("/invoices/{$inv['id']}/unpaid") ?>" class="inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="p-1.5 hover:bg-yellow-100 rounded-lg transition-colors text-yellow-600" title="Visszavonás">
                                        <i class="fa-solid fa-rotate-left text-sm"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                                <?php if (Auth::isOwner()): ?>
                                <form method="POST" action="<?= base_url("/invoices/{$inv['id']}/delete") ?>" class="inline" onsubmit="return confirm('Biztosan törli?')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="p-1.5 hover:bg-error-container/10 rounded-lg transition-colors text-on-surface-variant hover:text-error">
                                        <i class="fa-solid fa-trash text-sm"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (!empty($invoices)): ?>
    <div class="bg-surface-container-low px-8 py-4 border-t border-surface-container">
        <p class="text-xs font-medium text-on-surface-variant"><?= count($invoices) ?> számla</p>
    </div>
    <?php endif; ?>
</div>
