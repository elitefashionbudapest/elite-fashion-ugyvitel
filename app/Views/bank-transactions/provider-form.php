<?php
$banks = $data['banks'] ?? [];
$invoices = $data['invoices'] ?? [];
$inputCls = 'w-full px-4 py-3 border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary-container focus:border-primary bg-surface-container-lowest';
?>

<div class="max-w-lg">
    <div class="bg-surface-container-lowest rounded-xl p-6 sm:p-8">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-orange-100 flex items-center justify-center">
                <i class="fa-solid fa-building text-orange-600"></i>
            </div>
            <div>
                <h3 class="font-heading font-bold text-on-surface text-xl">Szolgáltatói levonás</h3>
                <p class="text-xs text-on-surface-variant">Bankszámláról levont szolgáltatói díj rögzítése (pl. Google, stb.).</p>
            </div>
        </div>

        <form method="POST" action="<?= base_url('/bank-transactions/provider') ?>" class="space-y-5">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Melyik bankszámláról vonták le?</label>
                <select name="bank_id" class="<?= $inputCls ?>" required>
                    <option value="">— Válasszon bankot —</option>
                    <?php foreach ($banks as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= old('bank_id') == $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Szolgáltató neve</label>
                <input type="text" name="provider_name" value="<?= e(old('provider_name')) ?>" class="<?= $inputCls ?>" placeholder="pl. Google, Telenor, stb." required>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Levonás dátuma</label>
                    <input type="date" name="transaction_date" value="<?= e(old('transaction_date', date('Y-m-d'))) ?>" class="<?= $inputCls ?>" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Összeg</label>
                    <div class="relative">
                        <input type="number" name="amount" step="1" min="0" value="<?= e(old('amount')) ?>"
                               class="<?= $inputCls ?> font-bold" placeholder="0" required>
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold text-on-surface-variant">Ft</span>
                    </div>
                </div>
            </div>

            <!-- Számla összekötés (opcionális) -->
            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Számla összekötés (opcionális)</label>
                <select name="invoice_id" class="<?= $inputCls ?>">
                    <option value="">— Később is összeköthetö —</option>
                    <?php foreach ($invoices as $inv): ?>
                        <option value="<?= $inv['id'] ?>"><?= e($inv['supplier_name']) ?> — <?= e($inv['invoice_number']) ?> (<?= format_money($inv['amount']) ?>)</option>
                    <?php endforeach; ?>
                </select>
                <p class="text-xs text-on-surface-variant mt-1">
                    <i class="fa-solid fa-circle-info mr-0.5"></i>
                    Ha a számla még nem érkezett meg, hónap végén a listából összeköthetöd.
                </p>
            </div>

            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Megjegyzés (opcionális)</label>
                <input type="text" name="notes" value="<?= e(old('notes')) ?>" class="<?= $inputCls ?>" placeholder="pl. Március havi Google Ads díj">
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="px-6 py-3 bg-gradient-to-br from-primary to-primary-container text-on-primary-fixed font-bold rounded-full text-sm shadow-lg shadow-primary/10 hover:shadow-primary/20 transition-all flex items-center gap-2">
                    <i class="fa-solid fa-check"></i> Rögzítés
                </button>
                <a href="<?= base_url('/bank-transactions') ?>" class="px-6 py-3 text-on-surface-variant hover:text-on-surface text-sm font-medium">Mégse</a>
            </div>
        </form>
    </div>
</div>
