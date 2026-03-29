<?php
$allBanks = $data['banks'] ?? [];
$bankAccounts = array_values(array_filter($allBanks, fn($b) => !$b['is_loan']));
$loans = array_values(array_filter($allBanks, fn($b) => $b['is_loan']));
?>

<div class="flex flex-wrap flex-col md:flex-row md:items-end justify-between gap-4 mb-6">
    <div>
        <h1 class="text-3xl font-heading font-extrabold text-on-surface tracking-tight mb-1">Bankszámlák és hitelek</h1>
        <p class="text-on-surface-variant text-sm">Egyenleg követés, bankszámlák és hitelek kezelése.</p>
    </div>
    <div class="flex gap-2">
        <a href="<?= base_url('/banks/create') ?>" class="px-4 py-2 bg-gradient-to-br from-primary to-primary-container text-on-primary-fixed font-bold rounded-full flex items-center gap-1.5 shadow-lg shadow-primary/10 text-sm">
            <i class="fa-solid fa-plus"></i> Bankszámla
        </a>
        <a href="<?= base_url('/banks/create?type=loan') ?>" class="px-4 py-2 bg-surface-container-low text-on-surface font-bold rounded-full flex items-center gap-1.5 text-sm border border-outline-variant">
            <i class="fa-solid fa-plus"></i> Hitel
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <!-- Bankszámlák -->
    <div class="bg-surface-container-lowest rounded-xl overflow-hidden">
        <div class="px-5 py-3 border-b border-surface-container bg-surface-container-low/50">
            <h2 class="font-heading font-bold text-on-surface text-sm flex items-center gap-2">
                <i class="fa-solid fa-building-columns text-blue-600"></i> Bankszámlák
            </h2>
        </div>
        <div class="divide-y divide-surface-container">
            <?php if (empty($bankAccounts)): ?>
                <div class="px-5 py-6 text-center text-on-surface-variant text-sm">Nincs bankszámla.</div>
            <?php else: ?>
                <?php foreach ($bankAccounts as $bank): ?>
                <?php $balance = $bank['balance'] ?? 0; ?>
                <div class="px-5 py-3 hover:bg-surface-container-low/50 transition-colors flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg <?= $bank['currency'] !== 'HUF' ? 'bg-indigo-100' : 'bg-blue-100' ?> flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid <?= $bank['currency'] !== 'HUF' ? 'fa-globe' : 'fa-building-columns' ?> <?= $bank['currency'] !== 'HUF' ? 'text-indigo-600' : 'text-blue-600' ?> text-xs"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-on-surface text-sm truncate"><?= e($bank['name']) ?></span>
                            <?php if ($bank['currency'] !== 'HUF'): ?>
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-700"><?= e($bank['currency']) ?></span>
                            <?php endif; ?>
                            <?php if (!$bank['is_active']): ?>
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-gray-100 text-gray-500">Inaktív</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($bank['account_number']): ?>
                            <span class="text-[10px] font-mono text-on-surface-variant"><?= e($bank['account_number']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <?php if ($bank['currency'] !== 'HUF'): ?>
                            <span class="font-heading font-bold text-sm <?= $balance >= 0 ? 'text-emerald-600' : 'text-red-600' ?>">
                                <?= e(\App\Core\ExchangeRate::formatWithHuf($balance, $bank['currency'])) ?>
                            </span>
                            <p class="text-[9px] text-on-surface-variant"><?= e(\App\Core\ExchangeRate::getRateText($bank['currency'])) ?></p>
                        <?php else: ?>
                            <span class="font-heading font-bold text-sm <?= $balance >= 0 ? 'text-emerald-600' : 'text-red-600' ?>"><?= format_money($balance) ?></span>
                            <?php if ($bank['min_balance'] !== null && $bank['min_balance'] < 0 && $balance < 0): ?>
                                <?php $used = abs($bank['min_balance']) > 0 ? round(abs($balance) / abs($bank['min_balance']) * 100) : 0; ?>
                                <p class="text-[9px] text-on-surface-variant">Limit: <?= format_money($bank['min_balance']) ?> <span class="font-bold <?= $used >= 80 ? 'text-red-500' : 'text-amber-500' ?>">(<?= $used ?>%)</span></p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <div class="flex-shrink-0 flex gap-0.5">
                        <a href="<?= base_url("/banks/{$bank['id']}/edit") ?>" class="p-1.5 hover:bg-surface-container rounded-full text-on-surface-variant text-xs"><i class="fa-solid fa-pen-to-square"></i></a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Hitelek -->
    <div class="bg-surface-container-lowest rounded-xl overflow-hidden">
        <div class="px-5 py-3 border-b border-surface-container bg-surface-container-low/50">
            <h2 class="font-heading font-bold text-on-surface text-sm flex items-center gap-2">
                <i class="fa-solid fa-landmark text-amber-600"></i> Hitelek
            </h2>
        </div>
        <div class="divide-y divide-surface-container">
            <?php if (empty($loans)): ?>
                <div class="px-5 py-6 text-center text-on-surface-variant text-sm">Nincs hitel.</div>
            <?php else: ?>
                <?php foreach ($loans as $loan): ?>
                <?php
                    $balance = $loan['balance'] ?? 0;
                    $opening = (float)$loan['opening_balance'];
                    $paid = abs($balance - $opening);
                    $paidPct = $opening != 0 ? round(($paid / abs($opening)) * 100) : 0;
                ?>
                <div class="px-5 py-3 hover:bg-surface-container-low/50 transition-colors flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0">
                        <i class="fa-solid fa-landmark text-amber-600 text-xs"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <span class="font-bold text-on-surface text-sm truncate block"><?= e($loan['name']) ?></span>
                        <div class="flex items-center gap-2 mt-0.5">
                            <div class="w-16 bg-gray-200 rounded-full h-1.5">
                                <div class="bg-emerald-500 h-1.5 rounded-full" style="width: <?= min($paidPct, 100) ?>%"></div>
                            </div>
                            <span class="text-[10px] font-bold text-emerald-600"><?= $paidPct ?>%</span>
                            <?php if ($balance >= 0): ?>
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700">Visszafizetve</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <span class="font-heading font-bold text-sm text-red-600"><?= format_money(abs($balance)) ?></span>
                        <p class="text-[9px] text-on-surface-variant">/ <?= format_money(abs($opening)) ?></p>
                    </div>
                    <div class="flex-shrink-0">
                        <a href="<?= base_url("/banks/{$loan['id']}/edit") ?>" class="p-1.5 hover:bg-surface-container rounded-full text-on-surface-variant text-xs"><i class="fa-solid fa-pen-to-square"></i></a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
