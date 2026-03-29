<?php
$bank = $data['bank'] ?? null;
$isLoan = $bank ? $bank['is_loan'] : (($_GET['type'] ?? '') === 'loan' ? 1 : 0);
$inputCls = 'w-full px-4 py-3 border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary-container focus:border-primary bg-surface-container-lowest';
$curSymbols = ['HUF' => 'Ft', 'EUR' => '€', 'USD' => '$', 'GBP' => '£'];
$currentCurrency = $bank ? ($bank['currency'] ?? 'HUF') : old('currency', 'HUF');
?>
<div class="max-w-lg">
    <div class="bg-surface-container-lowest rounded-xl p-6 sm:p-8">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl <?= $isLoan ? 'bg-amber-100' : 'bg-blue-100' ?> flex items-center justify-center">
                <i class="fa-solid <?= $isLoan ? 'fa-landmark text-amber-600' : 'fa-building-columns text-blue-600' ?>"></i>
            </div>
            <h3 class="font-heading font-bold text-on-surface text-xl">
                <?= $bank ? ($isLoan ? 'Hitel szerkesztése' : 'Bankszámla szerkesztése') : ($isLoan ? 'Új hitel' : 'Új bankszámla') ?>
            </h3>
        </div>

        <form method="POST" action="<?= base_url($bank ? "/banks/{$bank['id']}" : '/banks') ?>" class="space-y-5">
            <?= csrf_field() ?>
            <input type="hidden" name="is_loan" value="<?= $isLoan ?>">

            <div class="grid grid-cols-3 gap-3">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-on-surface mb-1.5"><?= $isLoan ? 'Hitel neve' : 'Bank neve' ?></label>
                    <input type="text" name="name" value="<?= e($bank ? $bank['name'] : old('name')) ?>"
                           class="<?= $inputCls ?>" placeholder="<?= $isLoan ? 'pl. Széchenyi Hitel 1' : 'pl. OTP Bank' ?>" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-on-surface mb-1.5">Pénznem</label>
                    <select name="currency" id="currency-select" class="<?= $inputCls ?>" onchange="updateCurrencyUI()">
                        <?php $cur = $currentCurrency; ?>
                        <option value="HUF" <?= $cur === 'HUF' ? 'selected' : '' ?>>HUF (Ft)</option>
                        <option value="EUR" <?= $cur === 'EUR' ? 'selected' : '' ?>>EUR (€)</option>
                        <option value="USD" <?= $cur === 'USD' ? 'selected' : '' ?>>USD ($)</option>
                        <option value="GBP" <?= $cur === 'GBP' ? 'selected' : '' ?>>GBP (£)</option>
                    </select>
                </div>
            </div>

            <?php if (!$isLoan): ?>
            <div>
                <label class="block text-sm font-medium text-on-surface mb-1.5">Számlaszám (opcionális)</label>
                <input type="text" name="account_number" id="account_number" value="<?= e($bank ? $bank['account_number'] : old('account_number')) ?>"
                       class="<?= $inputCls ?> font-mono" placeholder="11111111-22222222-33333333"
                       oninput="formatAccountNumber(this)">
            </div>
            <?php endif; ?>

            <div>
                <label class="block text-sm font-medium text-on-surface mb-1.5"><?= $isLoan ? 'Induló tartozás (negatív összeg)' : 'Nyitó egyenleg' ?></label>
                <div class="relative">
                    <input type="number" name="opening_balance" step="0.01"
                           value="<?= e($bank ? $bank['opening_balance'] : old('opening_balance', '0')) ?>"
                           class="<?= $inputCls ?>" placeholder="<?= $isLoan ? 'pl. -5000000' : '0' ?>">
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold text-on-surface-variant currency-symbol"><?= $curSymbols[$currentCurrency] ?? 'Ft' ?></span>
                </div>
                <?php if ($isLoan): ?>
                    <p class="text-xs text-on-surface-variant mt-1">Add meg negatív számként a teljes hiteltartozást (pl. -5000000).</p>
                <?php else: ?>
                    <p class="text-xs text-on-surface-variant mt-1">Az egyenleg ettől az összegtől indul.</p>
                <?php endif; ?>
            </div>

            <?php if (!$isLoan): ?>
            <div>
                <label class="block text-sm font-medium text-on-surface mb-1.5">Minimális egyenleg (opcionális)</label>
                <div class="relative">
                    <input type="number" name="min_balance" step="0.01"
                           value="<?= e($bank && $bank['min_balance'] !== null ? $bank['min_balance'] : old('min_balance')) ?>"
                           class="<?= $inputCls ?>" placeholder="Nincs limit">
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold text-on-surface-variant currency-symbol"><?= $curSymbols[$currentCurrency] ?? 'Ft' ?></span>
                </div>
                <p class="text-xs text-on-surface-variant mt-1">Hitelkeret esetén negatív érték. Üresen hagyva nincs limit.</p>
            </div>
            <?php endif; ?>

            <div>
                <label class="block text-sm font-medium text-on-surface mb-1.5">Megjegyzés (opcionális)</label>
                <input type="text" name="notes" value="<?= e($bank ? $bank['notes'] : old('notes')) ?>"
                       class="<?= $inputCls ?>" placeholder="<?= $isLoan ? 'pl. Futamidő, kamat, stb.' : 'pl. Fő üzleti számla' ?>">
            </div>

            <?php if ($bank): ?>
            <div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" <?= $bank['is_active'] ? 'checked' : '' ?>
                           class="h-4 w-4 text-primary border-outline rounded focus:ring-primary-container">
                    <span class="text-sm text-on-surface">Aktív</span>
                </label>
            </div>
            <?php endif; ?>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="px-6 py-3 bg-gradient-to-br from-primary to-primary-container text-on-primary-fixed font-bold rounded-full text-sm shadow-lg shadow-primary/10 hover:shadow-primary/20 transition-all">
                    <?= $bank ? 'Mentés' : 'Létrehozás' ?>
                </button>
                <a href="<?= base_url('/banks') ?>" class="text-on-surface-variant hover:text-on-surface text-sm font-medium">Mégse</a>
            </div>
        </form>
    </div>
</div>

<script>
const symbols = {'HUF':'Ft','EUR':'€','USD':'$','GBP':'£'};

function formatAccountNumber(input) {
    const currency = document.getElementById('currency-select').value;
    if (currency !== 'HUF') return; // Csak magyar számlaszámnál formáz

    let v = input.value.replace(/[^0-9]/g, '');
    let formatted = '';
    for (let i = 0; i < v.length && i < 24; i++) {
        if (i > 0 && i % 8 === 0) formatted += '-';
        formatted += v[i];
    }
    input.value = formatted;
}

function updateCurrencyUI() {
    const cur = document.getElementById('currency-select').value;
    const sym = symbols[cur] || cur;
    document.querySelectorAll('.currency-symbol').forEach(el => el.textContent = sym);

    // Placeholder frissítés
    const accInput = document.getElementById('account_number');
    if (accInput) {
        accInput.placeholder = cur === 'HUF' ? '11111111-22222222-33333333' : 'pl. GB29 NWBK 6016 1331 9268 19';
    }
}
</script>
