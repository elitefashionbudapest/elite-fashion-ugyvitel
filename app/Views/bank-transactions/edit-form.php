<?php
use App\Models\BankTransaction;
$tx = $data['transaction'] ?? null;
$banks = $data['banks'] ?? [];
$loans = $data['loans'] ?? [];
$stores = $data['stores'] ?? [];
$inputCls = 'w-full px-4 py-3 border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary-container focus:border-primary bg-surface-container-lowest';

$typeLabels = BankTransaction::TYPES;
$typeIcons = [
    'kartya_beerkezes' => ['icon' => 'fa-credit-card', 'color' => 'text-blue-600', 'bg' => 'bg-blue-100'],
    'szolgaltato_levon' => ['icon' => 'fa-building', 'color' => 'text-orange-600', 'bg' => 'bg-orange-100'],
    'hitel_torlesztes' => ['icon' => 'fa-landmark', 'color' => 'text-amber-600', 'bg' => 'bg-amber-100'],
    'szamla_kozti' => ['icon' => 'fa-arrow-right-arrow-left', 'color' => 'text-indigo-600', 'bg' => 'bg-indigo-100'],
    'banki_jutalek' => ['icon' => 'fa-percent', 'color' => 'text-red-600', 'bg' => 'bg-red-100'],
    'tulajdonosi_fizetes' => ['icon' => 'fa-user-tie', 'color' => 'text-purple-600', 'bg' => 'bg-purple-100'],
    'tagi_kolcson_be' => ['icon' => 'fa-handshake', 'color' => 'text-emerald-600', 'bg' => 'bg-emerald-100'],
    'tagi_kolcson_ki' => ['icon' => 'fa-handshake', 'color' => 'text-rose-600', 'bg' => 'bg-rose-100'],
];
$ti = $typeIcons[$tx['type']] ?? ['icon' => 'fa-circle', 'color' => 'text-gray-600', 'bg' => 'bg-gray-100'];
?>

<div class="max-w-lg">
    <div class="bg-surface-container-lowest rounded-xl p-6 sm:p-8">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl <?= $ti['bg'] ?> flex items-center justify-center">
                <i class="fa-solid <?= $ti['icon'] ?> <?= $ti['color'] ?>"></i>
            </div>
            <div>
                <h3 class="font-heading font-bold text-on-surface text-xl">Tranzakció szerkesztése</h3>
                <p class="text-xs text-on-surface-variant"><?= e($typeLabels[$tx['type']] ?? $tx['type']) ?></p>
            </div>
        </div>

        <form method="POST" action="<?= base_url("/bank-transactions/{$tx['id']}") ?>" class="space-y-5">
            <?= csrf_field() ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Bankszámla</label>
                    <select name="bank_id" class="<?= $inputCls ?>" required>
                        <?php foreach ($banks as $b): ?>
                            <option value="<?= $b['id'] ?>" <?= $tx['bank_id'] == $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Dátum</label>
                    <input type="date" name="transaction_date" value="<?= e($tx['transaction_date']) ?>" class="<?= $inputCls ?>" required>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">
                        <?= $tx['type'] === 'szamla_kozti' ? 'Érkezett összeg' : 'Összeg' ?>
                    </label>
                    <div class="relative">
                        <input type="text" inputmode="decimal" data-calc name="amount" step="0.01" min="0" value="<?= e($tx['amount']) ?>" class="<?= $inputCls ?> font-bold" required>
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold text-on-surface-variant">Ft</span>
                    </div>
                </div>

                <?php if ($tx['type'] === 'szamla_kozti'): ?>
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Küldött összeg</label>
                    <div class="relative">
                        <input type="text" inputmode="decimal" data-calc name="source_amount" step="0.01" min="0" value="<?= e($tx['source_amount']) ?>" class="<?= $inputCls ?> font-bold">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold text-on-surface-variant">Ft</span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($tx['type'] === 'kartya_beerkezes'): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Időszak mettől</label>
                    <input type="date" name="date_from" value="<?= e($tx['date_from']) ?>" class="<?= $inputCls ?>">
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Időszak meddig</label>
                    <input type="date" name="date_to" value="<?= e($tx['date_to']) ?>" class="<?= $inputCls ?>">
                </div>
            </div>
            <?php endif; ?>

            <?php if ($tx['type'] === 'szolgaltato_levon'): ?>
            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Szolgáltató neve</label>
                <input type="text" name="provider_name" value="<?= e($tx['provider_name']) ?>" class="<?= $inputCls ?>">
            </div>
            <?php endif; ?>

            <?php if ($tx['type'] === 'hitel_torlesztes'): ?>
            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Melyik hitelt törleszti?</label>
                <select name="loan_bank_id" class="<?= $inputCls ?>">
                    <?php foreach ($loans as $l): ?>
                        <option value="<?= $l['id'] ?>" <?= $tx['loan_bank_id'] == $l['id'] ? 'selected' : '' ?>><?= e($l['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if ($tx['type'] === 'szamla_kozti'): ?>
            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Fogadó számla</label>
                <select name="target_bank_id" class="<?= $inputCls ?>">
                    <?php foreach ($banks as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= $tx['target_bank_id'] == $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Megjegyzés</label>
                <input type="text" name="notes" value="<?= e($tx['notes']) ?>" class="<?= $inputCls ?>" placeholder="Opcionális">
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="px-6 py-3 bg-gradient-to-br from-primary to-primary-container text-on-primary-fixed font-bold rounded-full text-sm shadow-lg flex items-center gap-2">
                    <i class="fa-solid fa-check"></i> Mentés
                </button>
                <a href="<?= base_url('/bank-transactions') ?>" class="px-6 py-3 text-on-surface-variant hover:text-on-surface text-sm font-medium">Mégse</a>
            </div>
        </form>
    </div>
</div>
