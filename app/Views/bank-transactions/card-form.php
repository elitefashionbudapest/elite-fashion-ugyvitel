<?php
$banks = $data['banks'] ?? [];
$stores = $data['stores'] ?? [];
$inputCls = 'w-full px-4 py-3 border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary-container focus:border-primary bg-surface-container-lowest';
?>

<div class="max-w-3xl">
    <div class="bg-surface-container-lowest rounded-xl p-6 sm:p-8">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                <i class="fa-solid fa-credit-card text-blue-600"></i>
            </div>
            <div>
                <h3 class="font-heading font-bold text-on-surface text-xl">Kártyás forgalom beérkezés</h3>
                <p class="text-xs text-on-surface-variant">A bankkártyás forgalom beérkezésének rögzítése a bankszámlára.</p>
            </div>
        </div>

        <form method="POST" action="<?= base_url('/bank-transactions/card') ?>" class="space-y-5">
            <?= csrf_field() ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Melyik bankszámlára érkezett -->
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Melyik bankszámlára érkezett?</label>
                    <select name="bank_id" class="<?= $inputCls ?>" required>
                        <option value="">— Válasszon bankot —</option>
                        <?php foreach ($banks as $b): ?>
                            <option value="<?= $b['id'] ?>" <?= old('bank_id') == $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Beérkezés dátuma -->
                <div>
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Beérkezés dátuma</label>
                    <input type="date" name="transaction_date" value="<?= e(old('transaction_date', date('Y-m-d'))) ?>" class="<?= $inputCls ?>" required>
                </div>
            </div>

            <!-- Időszak (melyik napok forgalma) -->
            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Melyik napok forgalma?</label>
                <div class="flex items-center gap-2">
                    <input type="date" name="date_from" id="date_from" value="<?= e(old('date_from')) ?>" class="<?= $inputCls ?>" required>
                    <span class="text-on-surface-variant font-bold">—</span>
                    <input type="date" name="date_to" id="date_to" value="<?= e(old('date_to')) ?>" class="<?= $inputCls ?>" required>
                </div>
                <p class="text-xs text-on-surface-variant mt-1">
                    <i class="fa-solid fa-circle-info mr-0.5"></i>
                    Hétfőn a péntek–vasárnap forgalmát add meg. Más napokon az előző nap dátumát.
                </p>
            </div>

            <!-- Melyik boltok forgalma -->
            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Melyik boltok forgalma?</label>
                <div class="flex flex-wrap gap-3">
                    <?php foreach ($stores as $s): ?>
                    <label class="flex items-center gap-2 cursor-pointer px-4 py-3 rounded-xl border border-surface-container hover:border-primary-container transition-all has-[:checked]:border-primary has-[:checked]:bg-primary-container/15 has-[:checked]:shadow-sm">
                        <input type="checkbox" name="store_ids[]" value="<?= $s['id'] ?>"
                               onchange="updateGross()"
                               class="h-4 w-4 text-primary border-outline rounded focus:ring-primary-container">
                        <i class="fa-solid fa-store text-sm text-on-surface-variant"></i>
                        <span class="text-sm font-medium"><?= e($s['name']) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Bruttó összeg (számított) + Nettó összeg (beírt) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-surface-container-low/50 rounded-xl p-4 border border-surface-container">
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1">Bolti forgalom (bruttó)</label>
                    <div class="text-xl font-heading font-bold text-on-surface" id="gross-display">0 Ft</div>
                    <input type="hidden" id="gross-value" value="0">
                    <p class="text-[10px] text-on-surface-variant mt-1">A könyvelésből számított érték</p>
                </div>

                <div class="bg-blue-50 rounded-xl p-4 border border-blue-200">
                    <label class="block text-xs font-bold text-blue-700 uppercase tracking-widest mb-1">Beérkezett összeg (nettó)</label>
                    <div class="relative">
                        <input type="number" name="amount" id="net-amount" step="1" min="0" value="<?= e(old('amount')) ?>"
                               class="w-full px-3 py-2 border border-blue-300 rounded-lg text-lg font-heading font-bold text-center focus:ring-2 focus:ring-blue-200 focus:border-blue-400 bg-white"
                               placeholder="0" required onchange="updateCommission()" oninput="updateCommission()">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-blue-400">Ft</span>
                    </div>
                </div>

                <div class="bg-red-50 rounded-xl p-4 border border-red-200">
                    <label class="block text-xs font-bold text-red-600 uppercase tracking-widest mb-1">Bank jutalék</label>
                    <div class="text-xl font-heading font-bold text-red-600" id="commission-display">0 Ft</div>
                    <div class="text-[10px] text-red-400 mt-1" id="commission-percent"></div>
                </div>
            </div>

            <!-- Megjegyzés -->
            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Megjegyzés (opcionális)</label>
                <input type="text" name="notes" value="<?= e(old('notes')) ?>" class="<?= $inputCls ?>" placeholder="pl. Hétvégi forgalom">
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
let grossValue = 0;

async function updateGross() {
    const checked = document.querySelectorAll('input[name="store_ids[]"]:checked');
    const dateFrom = document.getElementById('date_from').value;
    const dateTo = document.getElementById('date_to').value;

    if (checked.length === 0 || !dateFrom || !dateTo) {
        grossValue = 0;
        document.getElementById('gross-display').textContent = '0 Ft';
        document.getElementById('gross-value').value = '0';
        updateCommission();
        return;
    }

    const params = new URLSearchParams();
    checked.forEach(cb => params.append('store_ids[]', cb.value));
    params.set('date_from', dateFrom);
    params.set('date_to', dateTo);

    try {
        const resp = await fetch('<?= base_url('/bank-transactions/api/gross') ?>?' + params);
        const data = await resp.json();
        grossValue = data.gross || 0;
        document.getElementById('gross-display').textContent = new Intl.NumberFormat('hu-HU').format(grossValue) + ' Ft';
        document.getElementById('gross-value').value = grossValue;
        updateCommission();
    } catch (e) {
        grossValue = 0;
    }
}

function updateCommission() {
    const net = parseFloat(document.getElementById('net-amount').value) || 0;
    const commission = grossValue - net;
    document.getElementById('commission-display').textContent = new Intl.NumberFormat('hu-HU').format(commission) + ' Ft';

    if (grossValue > 0 && net > 0) {
        const pct = ((commission / grossValue) * 100).toFixed(2);
        document.getElementById('commission-percent').textContent = pct + '% jutalék';
    } else {
        document.getElementById('commission-percent').textContent = '';
    }
}

document.getElementById('date_from').addEventListener('change', updateGross);
document.getElementById('date_to').addEventListener('change', updateGross);
</script>
