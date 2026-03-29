<?php
use App\Core\ExchangeRate;
$banks = $data['banks'] ?? [];
$inputCls = 'w-full px-4 py-3 border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary-container focus:border-primary bg-surface-container-lowest';
?>

<div class="max-w-lg">
    <div class="bg-surface-container-lowest rounded-xl p-6 sm:p-8">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center">
                <i class="fa-solid fa-arrow-right-arrow-left text-indigo-600"></i>
            </div>
            <div>
                <h3 class="font-heading font-bold text-on-surface text-xl">Számlák közötti átutalás</h3>
                <p class="text-xs text-on-surface-variant">Pénz mozgatása egyik bankszámláról a másikra. Deviza átváltás is.</p>
            </div>
        </div>

        <form method="POST" action="<?= base_url('/bank-transactions/transfer') ?>" class="space-y-5">
            <?= csrf_field() ?>

            <!-- Honnan -->
            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Honnan?</label>
                <select name="bank_id" id="source_bank" class="<?= $inputCls ?>" required onchange="updateCurrencyInfo()">
                    <option value="">— Küldő számla —</option>
                    <?php foreach ($banks as $b): ?>
                        <option value="<?= $b['id'] ?>" data-currency="<?= e($b['currency']) ?>" <?= old('bank_id') == $b['id'] ? 'selected' : '' ?>>
                            <?= e($b['name']) ?> (<?= e($b['currency']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Hová -->
            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Hová?</label>
                <select name="target_bank_id" id="target_bank" class="<?= $inputCls ?>" required onchange="updateCurrencyInfo()">
                    <option value="">— Fogadó számla —</option>
                    <?php foreach ($banks as $b): ?>
                        <option value="<?= $b['id'] ?>" data-currency="<?= e($b['currency']) ?>" <?= old('target_bank_id') == $b['id'] ? 'selected' : '' ?>>
                            <?= e($b['name']) ?> (<?= e($b['currency']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Dátum -->
            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Átutalás dátuma</label>
                <input type="date" name="transaction_date" value="<?= e(old('transaction_date', date('Y-m-d'))) ?>" class="<?= $inputCls ?>" required>
            </div>

            <!-- Összegek -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Küldött összeg</label>
                    <div class="relative">
                        <input type="number" name="source_amount" id="source_amount" step="0.01" min="0" value="<?= e(old('source_amount')) ?>"
                               class="<?= $inputCls ?> font-bold" placeholder="0" required>
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold text-on-surface-variant" id="source_currency_label">Ft</span>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Érkezett összeg</label>
                    <div class="relative">
                        <input type="number" name="amount" id="target_amount" step="0.01" min="0" value="<?= e(old('amount')) ?>"
                               class="<?= $inputCls ?> font-bold" placeholder="0" required>
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold text-on-surface-variant" id="target_currency_label">Ft</span>
                    </div>
                </div>
            </div>

            <!-- Árfolyam info -->
            <div id="rate-info" class="hidden bg-indigo-50 border border-indigo-200 rounded-xl p-3">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-indigo-700"><i class="fa-solid fa-chart-line mr-1"></i>Tényleges árfolyam:</span>
                    <span class="font-bold text-indigo-800" id="actual-rate"></span>
                </div>
                <div class="flex items-center justify-between text-xs mt-1">
                    <span class="text-indigo-500">Jutalék + árfolyamkülönbség:</span>
                    <span class="font-bold text-red-500" id="fee-display"></span>
                </div>
            </div>

            <!-- Megjegyzés -->
            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Megjegyzés (opcionális)</label>
                <input type="text" name="notes" value="<?= e(old('notes')) ?>" class="<?= $inputCls ?>" placeholder="pl. WISE feltöltés">
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

<script>
const currencySymbols = {'HUF':'Ft','USD':'$','EUR':'€','GBP':'£'};

function getCurrency(selectId) {
    const sel = document.getElementById(selectId);
    const opt = sel.options[sel.selectedIndex];
    return opt?.dataset?.currency || 'HUF';
}

function updateCurrencyInfo() {
    const srcCur = getCurrency('source_bank');
    const tgtCur = getCurrency('target_bank');
    document.getElementById('source_currency_label').textContent = currencySymbols[srcCur] || srcCur;
    document.getElementById('target_currency_label').textContent = currencySymbols[tgtCur] || tgtCur;
    calcRate();
}

function calcRate() {
    const srcAmount = parseFloat(document.getElementById('source_amount').value) || 0;
    const tgtAmount = parseFloat(document.getElementById('target_amount').value) || 0;
    const srcCur = getCurrency('source_bank');
    const tgtCur = getCurrency('target_bank');
    const rateInfo = document.getElementById('rate-info');

    if (srcCur !== tgtCur && srcAmount > 0 && tgtAmount > 0) {
        const rate = tgtAmount / srcAmount;
        document.getElementById('actual-rate').textContent = '1 ' + srcCur + ' = ' + rate.toFixed(4) + ' ' + tgtCur;

        // Napi piaci árfolyam összehasonlítás (ha van)
        rateInfo.classList.remove('hidden');
    } else if (srcCur === tgtCur && srcAmount > 0 && tgtAmount > 0 && srcAmount !== tgtAmount) {
        const fee = srcAmount - tgtAmount;
        document.getElementById('actual-rate').textContent = 'Azonos pénznem';
        document.getElementById('fee-display').textContent = format(fee) + ' ' + (currencySymbols[srcCur]||srcCur) + ' jutalék';
        rateInfo.classList.remove('hidden');
    } else {
        rateInfo.classList.add('hidden');
    }
}

function format(n) { return new Intl.NumberFormat('hu-HU').format(Math.round(n)); }

document.getElementById('source_amount').addEventListener('input', calcRate);
document.getElementById('target_amount').addEventListener('input', calcRate);
</script>
