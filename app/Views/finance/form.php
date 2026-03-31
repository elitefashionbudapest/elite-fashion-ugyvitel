<?php
use App\Core\Auth;
use App\Models\FinancialRecord;

$record = $data['record'] ?? null;
$stores = $data['stores'] ?? [];
$employees = $data['employees'] ?? [];
$banks = $data['banks'] ?? [];
$isEdit = $record !== null;
$inputCls = 'w-full px-4 py-3 border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary-container focus:border-primary bg-surface-container-lowest';

// Típusok csoportosítva
$purposeGroups = [
    'Bevételek' => [
        'napi_keszpenz'    => ['label' => 'Napi KÉSZPÉNZ forgalom', 'icon' => 'fa-money-bill', 'color' => 'text-emerald-600'],
        'napi_bankkartya'  => ['label' => 'Napi BANKKÁRTYA forgalom', 'icon' => 'fa-credit-card', 'color' => 'text-blue-600'],
        'befizetes_bankbol'=> ['label' => 'Befizetés bankból', 'icon' => 'fa-building-columns', 'color' => 'text-emerald-500'],
        'befizetes_boltbol'=> ['label' => 'Befizetés másik boltból', 'icon' => 'fa-store', 'color' => 'text-emerald-500'],
        'selejt_befizetes' => ['label' => 'Selejt befizetés', 'icon' => 'fa-box-open', 'color' => 'text-emerald-500'],
        'kassza_nyito'     => ['label' => 'Kassza NYITÓ összeg', 'icon' => 'fa-cash-register', 'color' => 'text-amber-600'],
    ],
    'Kiadások' => [
        'meretre_igazitas' => ['label' => 'Méretre igazítás kifizetés', 'icon' => 'fa-ruler', 'color' => 'text-red-500'],
        'tankolas'         => ['label' => 'Tankolás', 'icon' => 'fa-gas-pump', 'color' => 'text-red-500'],
        'munkaber'         => ['label' => 'Munkabér kifizetés', 'icon' => 'fa-hand-holding-dollar', 'color' => 'text-red-500'],
        'egyeb_kifizetes'  => ['label' => 'Egyéb kifizetés', 'icon' => 'fa-file-invoice-dollar', 'color' => 'text-red-500'],
        'bank_kifizetes'   => ['label' => 'Befizetés bankba', 'icon' => 'fa-building-columns', 'color' => 'text-red-500'],
        'szamla_kifizetes' => ['label' => 'Bejövő számla kifizetés', 'icon' => 'fa-file-invoice', 'color' => 'text-red-500'],
    ],
];
?>

<div class="max-w-4xl">
    <div class="bg-surface-container-lowest rounded-xl p-6 sm:p-8">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-container/50 to-primary-container/20 flex items-center justify-center">
                <i class="fa-solid fa-building-columns text-on-primary-container"></i>
            </div>
            <div>
                <h3 class="font-heading font-bold text-on-surface text-xl"><?= $isEdit ? 'Pénzmozgás szerkesztése' : 'Pénzmozgás rögzítés' ?></h3>
                <p class="text-xs text-on-surface-variant">Válassza ki a típust, majd adja meg az összeget.</p>
            </div>
        </div>

        <form method="POST" action="<?= base_url($isEdit ? "/finance/{$record['id']}" : '/finance') ?>">
            <?= csrf_field() ?>

            <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
                <!-- BAL: Típus választó (3 oszlop széles) -->
                <div class="md:col-span-3">
                    <!-- Bolt + Dátum egy sorban -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-5">
                        <?php if (Auth::isOwner()): ?>
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Üzlet</label>
                            <select name="store_id" class="<?= $inputCls ?>" required>
                                <option value="">— Válasszon —</option>
                                <?php foreach ($stores as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= ($record['store_id'] ?? old('store_id')) == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Dátum</label>
                            <input type="date" name="record_date" value="<?= e($record['record_date'] ?? old('record_date', date('Y-m-d'))) ?>" class="<?= $inputCls ?>" required>
                        </div>
                    </div>

                    <!-- Típus kártyák -->
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-2">Pénzmozgás célja</label>
                    <?php foreach ($purposeGroups as $groupName => $purposes): ?>
                    <p class="text-[10px] font-bold uppercase tracking-widest mb-1.5 mt-3 first:mt-0 <?= $groupName === 'Bevételek' ? 'text-emerald-600' : 'text-red-500' ?>">
                        <i class="fa-solid <?= $groupName === 'Bevételek' ? 'fa-arrow-down' : 'fa-arrow-up' ?> text-[8px] mr-0.5"></i><?= $groupName ?>
                    </p>
                    <div class="grid grid-cols-2 gap-2 mb-2">
                        <?php foreach ($purposes as $key => $p): ?>
                        <label class="flex items-center gap-2.5 cursor-pointer px-3 py-2.5 rounded-xl border border-surface-container hover:border-primary-container hover:bg-primary-container/5 transition-all has-[:checked]:border-primary has-[:checked]:bg-primary-container/15 has-[:checked]:shadow-sm">
                            <input type="radio" name="purpose" value="<?= $key ?>"
                                   <?= ($record['purpose'] ?? old('purpose')) === $key ? 'checked' : '' ?>
                                   onchange="toggleFields(this.value)"
                                   class="h-4 w-4 text-primary border-outline focus:ring-primary-container">
                            <i class="fa-solid <?= $p['icon'] ?> text-sm <?= $p['color'] ?>"></i>
                            <span class="text-sm font-medium text-on-surface"><?= e($p['label']) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- JOBB: Összeg + Extra mezők (2 oszlop széles) -->
                <div class="md:col-span-2 space-y-4">
                    <!-- Duplikáció figyelmeztetés -->
                    <div id="duplicate-warning" class="hidden bg-yellow-50 border border-yellow-300 rounded-xl p-3 flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation text-yellow-600"></i>
                        <span class="text-xs text-yellow-700 font-medium" id="duplicate-warning-text"></span>
                    </div>

                    <!-- Emlékeztető: munkabér → fizetések -->
                    <div id="reminder-munkaber" class="hidden bg-blue-50 border border-blue-300 rounded-xl p-3 flex items-start gap-2">
                        <i class="fa-solid fa-circle-info text-blue-600 mt-0.5"></i>
                        <span class="text-xs text-blue-700 font-medium">Ne felejtsd el a <a href="<?= base_url('/salary/create') ?>" class="underline font-bold hover:text-blue-900">Fizetések</a> menüpontban is rögzíteni!</span>
                    </div>

                    <!-- Emlékeztető: számla kifizetés → számlák -->
                    <div id="reminder-szamla" class="hidden bg-blue-50 border border-blue-300 rounded-xl p-3 flex items-start gap-2">
                        <i class="fa-solid fa-circle-info text-blue-600 mt-0.5"></i>
                        <span class="text-xs text-blue-700 font-medium">Ne felejtsd el a <a href="<?= base_url('/invoices') ?>" class="underline font-bold hover:text-blue-900">Számlák</a> menüpontban is rögzíteni, és kifizetettnek jelölni!</span>
                    </div>

                    <!-- Összeg kártya -->
                    <div class="bg-surface-container-low/50 rounded-xl p-5 border border-surface-container">
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-2" id="amount-label">Összeg</label>
                        <div class="relative">
                            <input type="text" inputmode="decimal" data-calc name="amount" id="amount" step="1" min="0"
                                   value="<?= e($record ? (int)$record['amount'] : old('amount')) ?>"
                                   class="w-full px-4 py-4 border border-outline-variant rounded-xl text-2xl font-heading font-bold text-center focus:ring-2 focus:ring-primary-container focus:border-primary bg-white"
                                   placeholder="0" required>
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm font-bold text-on-surface-variant">Ft</span>
                        </div>
                    </div>

                    <!-- Melyik bank? (bankból befizetés / bankba befizetés) -->
                    <div id="field-bank" class="hidden">
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Melyik bankszámla?</label>
                        <select name="bank_id" class="<?= $inputCls ?>">
                            <option value="">— Válasszon bankot —</option>
                            <?php foreach ($banks as $b): ?>
                                <option value="<?= $b['id'] ?>" <?= ($record['bank_id'] ?? old('bank_id')) == $b['id'] ? 'selected' : '' ?>><?= e($b['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Kinek? (munkabérnél) -->
                    <div id="field-paid-to" class="hidden">
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Kinek?</label>
                        <select name="paid_to_employee_id" class="<?= $inputCls ?>">
                            <option value="">— Válasszon dolgozót —</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= $emp['id'] ?>" <?= ($record['paid_to_employee_id'] ?? old('paid_to_employee_id')) == $emp['id'] ? 'selected' : '' ?>><?= e($emp['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Mire? (egyéb kifizetésnél) -->
                    <div id="field-description" class="hidden">
                        <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Mire?</label>
                        <input type="text" name="description" value="<?= e($record['description'] ?? old('description')) ?>" class="<?= $inputCls ?>" placeholder="Kifizetés célja...">
                    </div>

                    <!-- Gombok -->
                    <div class="flex gap-3 pt-3">
                        <button type="submit" class="flex-1 px-6 py-3.5 bg-gradient-to-br from-primary to-primary-container text-on-primary-fixed font-bold rounded-xl text-sm shadow-lg shadow-primary/10 hover:shadow-primary/20 transition-all flex items-center justify-center gap-2">
                            <i class="fa-solid fa-check"></i>
                            <?= $isEdit ? 'Mentés' : 'Rögzítés' ?>
                        </button>
                        <a href="<?= base_url('/finance') ?>" class="px-6 py-3.5 text-center text-on-surface-variant hover:text-on-surface text-sm font-medium bg-surface-container-low rounded-xl hover:bg-surface-container transition-colors flex items-center justify-center">
                            Mégse
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
const checkablePurposes = ['napi_keszpenz', 'napi_bankkartya', 'kassza_nyito'];
const bankPurposes = ['befizetes_bankbol', 'bank_kifizetes'];
const purposeLabels = {
    'napi_keszpenz':'Készpénz összeg','napi_bankkartya':'Bankkártya összeg',
    'meretre_igazitas':'Kifizetve (Méretre igazítás)','tankolas':'Kifizetve (Tankolás)',
    'munkaber':'Munkabér kifizetve','egyeb_kifizetes':'Egyéb kifizetés összege',
    'bank_kifizetes':'Bankba befizetett összeg','befizetes_bankbol':'Bankból kivett összeg',
    'befizetes_boltbol':'Másik boltból kapott összeg',
    'selejt_befizetes':'Selejt befizetés összege',
    'kassza_nyito':'Kassza NYITÓ összeg','szamla_kifizetes':'Számla kifizetés összege'
};
const purposeNames = { 'napi_keszpenz':'Napi KÉSZPÉNZ forgalom','napi_bankkartya':'Napi BANKKÁRTYA forgalom','kassza_nyito':'Kassza NYITÓ összeg' };

function toggleFields(p) {
    const paidTo = document.getElementById('field-paid-to');
    const paidToSelect = paidTo.querySelector('select');
    const bankField = document.getElementById('field-bank');
    const bankSelect = bankField.querySelector('select');

    paidTo.classList.toggle('hidden', p !== 'munkaber');
    document.getElementById('field-description').classList.toggle('hidden', p !== 'egyeb_kifizetes');
    document.getElementById('amount-label').textContent = purposeLabels[p] || 'Összeg';

    // Bank választó megjelenítése
    const needsBank = bankPurposes.includes(p);
    bankField.classList.toggle('hidden', !needsBank);
    if (bankSelect) bankSelect.required = needsBank;

    // Munkabérnél kötelező a dolgozó
    if (paidToSelect) paidToSelect.required = (p === 'munkaber');

    // Emlékeztetők
    document.getElementById('reminder-munkaber').classList.toggle('hidden', p !== 'munkaber');
    document.getElementById('reminder-szamla').classList.toggle('hidden', p !== 'szamla_kifizetes');
    checkDuplicate();
}
async function checkDuplicate() {
    const p = document.querySelector('input[name="purpose"]:checked')?.value;
    const d = document.querySelector('input[name="record_date"]')?.value;
    const s = document.querySelector('select[name="store_id"]')?.value || '';
    const w = document.getElementById('duplicate-warning'), wt = document.getElementById('duplicate-warning-text');
    if (!p||!d||!checkablePurposes.includes(p)) { w.classList.add('hidden'); return; }
    try { const r = await fetch('<?= base_url('/finance/check-duplicate') ?>?'+new URLSearchParams({purpose:p,date:d,store_id:s})); const data = await r.json();
    if (data.exists) { wt.textContent='Erre a napra már van "' + (purposeNames[p]||p) + '" rögzítve!'; w.classList.remove('hidden'); } else w.classList.add('hidden');
    } catch(e) { w.classList.add('hidden'); }
}
document.addEventListener('DOMContentLoaded', function() {
    const c = document.querySelector('input[name="purpose"]:checked'); if (c) toggleFields(c.value);
    document.querySelector('input[name="record_date"]')?.addEventListener('change', checkDuplicate);
    document.querySelector('select[name="store_id"]')?.addEventListener('change', checkDuplicate);
});
</script>
