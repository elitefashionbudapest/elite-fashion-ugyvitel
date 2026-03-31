<?php
$banks = $data['banks'] ?? [];
$inputCls = 'w-full px-4 py-3 border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary-container focus:border-primary bg-surface-container-lowest';
?>

<div class="max-w-lg">
    <div class="bg-surface-container-lowest rounded-xl p-6 sm:p-8">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center">
                <i class="fa-solid fa-handshake text-emerald-600"></i>
            </div>
            <div>
                <h3 class="font-heading font-bold text-on-surface text-xl">Tagi kölcsön</h3>
                <p class="text-xs text-on-surface-variant">Tagi kölcsön be- vagy kifizetése.</p>
            </div>
        </div>

        <form method="POST" action="<?= base_url('/bank-transactions/owner-loan') ?>" class="space-y-5">
            <?= csrf_field() ?>

            <!-- Irány -->
            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Irány</label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="flex items-center justify-center gap-2 cursor-pointer p-3 rounded-xl border-2 transition-all has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50 border-surface-container hover:border-emerald-300">
                        <input type="radio" name="direction" value="in" <?= old('direction', 'in') === 'in' ? 'checked' : '' ?>
                               class="h-4 w-4 text-emerald-600 border-outline focus:ring-emerald-300" required>
                        <i class="fa-solid fa-arrow-down text-emerald-600"></i>
                        <span class="text-sm font-bold text-emerald-700">Befizetés</span>
                    </label>
                    <label class="flex items-center justify-center gap-2 cursor-pointer p-3 rounded-xl border-2 transition-all has-[:checked]:border-red-500 has-[:checked]:bg-red-50 border-surface-container hover:border-red-300">
                        <input type="radio" name="direction" value="out" <?= old('direction') === 'out' ? 'checked' : '' ?>
                               class="h-4 w-4 text-red-600 border-outline focus:ring-red-300" required>
                        <i class="fa-solid fa-arrow-up text-red-600"></i>
                        <span class="text-sm font-bold text-red-700">Visszafizetés</span>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Bankszámla</label>
                    <select name="bank_id" class="<?= $inputCls ?>" required>
                        <option value="">-- Válasszon bankot --</option>
                        <?php foreach ($banks as $b): ?>
                            <option value="<?= $b['id'] ?>" <?= old('bank_id') == $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Dátum</label>
                    <input type="date" name="transaction_date" value="<?= e(old('transaction_date', date('Y-m-d'))) ?>" class="<?= $inputCls ?>" required>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Összeg</label>
                <div class="relative">
                    <input type="number" name="amount" step="0.01" min="0" value="<?= e(old('amount')) ?>"
                           class="w-full px-4 py-3 border border-outline-variant rounded-xl text-lg font-heading font-bold text-center focus:ring-2 focus:ring-primary-container focus:border-primary bg-surface-container-lowest"
                           placeholder="0" required>
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold text-on-surface-variant">Ft</span>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Megjegyzés (opcionális)</label>
                <input type="text" name="notes" value="<?= e(old('notes')) ?>" class="<?= $inputCls ?>" placeholder="pl. Ádám tagi kölcsön">
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
