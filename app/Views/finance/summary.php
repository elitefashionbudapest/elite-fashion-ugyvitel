<?php
$summary = $data['summary'] ?? [];
$dateFrom = $data['dateFrom'] ?? '';
$dateTo = $data['dateTo'] ?? '';
$kasszak = $data['kasszak'] ?? [];
$bankAccounts = $data['bankAccounts'] ?? [];
$loanAccounts = $data['loanAccounts'] ?? [];
$pl = $data['plCurrent'] ?? [];
$plPrev = $data['plPrev'] ?? [];
$plChange = $data['plChange'] ?? [];
$fc = $data['forecast'] ?? [];

// Változás badge helper
function changeBadge(?float $pct, bool $invertColor = false): string {
    if ($pct === null) return '';
    $color = $pct > 0 ? ($invertColor ? 'text-red-500' : 'text-emerald-600') : ($invertColor ? 'text-emerald-600' : 'text-red-500');
    $arrow = $pct > 0 ? '▲' : '▼';
    return '<span class="text-[10px] font-bold ' . $color . ' ml-1">' . $arrow . ' ' . abs($pct) . '%</span>';
}
?>

<!-- Eredménykimutatás (P&L) -->
<?php if (!empty($pl)): ?>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <!-- Bevétel vs Költség -->
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <h3 class="font-heading font-bold text-gray-900 mb-4 flex items-center gap-2">
            <i class="fa-solid fa-chart-pie text-primary"></i>
            Eredménykimutatás (P&L)
        </h3>

        <!-- Bevétel -->
        <div class="mb-4">
            <p class="text-[10px] font-bold uppercase tracking-widest text-emerald-600 mb-1"><i class="fa-solid fa-arrow-down text-[8px] mr-0.5"></i>Árbevétel</p>
            <div class="flex justify-between items-center py-1.5">
                <span class="text-sm text-gray-600">Bruttó forgalom</span>
                <span class="font-medium text-gray-400"><?= format_money($pl['revenue_brutto']) ?></span>
            </div>
            <div class="flex justify-between items-center py-1.5 border-b border-gray-200">
                <span class="text-sm font-semibold text-gray-700">Nettó árbevétel (ÁFA nélkül)</span>
                <span class="font-bold text-emerald-600"><?= format_money($pl['revenue_netto']) ?><?= changeBadge($plChange['revenue_netto'] ?? null) ?></span>
            </div>
        </div>

        <!-- Költségek -->
        <div class="mb-4">
            <p class="text-[10px] font-bold uppercase tracking-widest text-red-500 mb-1"><i class="fa-solid fa-arrow-up text-[8px] mr-0.5"></i>Költségek</p>
            <?php
            $costLines = [
                ['Munkabérek', $pl['munkaber'], 'fa-hand-holding-dollar'],
                ['Méretre igazítás', $pl['meretre'], 'fa-ruler'],
                ['Tankolás', $pl['tankolas'], 'fa-gas-pump'],
                ['Egyéb kifizetések', $pl['egyeb'], 'fa-file-invoice-dollar'],
                ['Számla kifizetések', $pl['szamla'], 'fa-file-invoice'],
                ['Bank jutalék', $pl['bank_jutalek'], 'fa-credit-card'],
                ['Szolgáltatók', $pl['szolgaltatok'], 'fa-building'],
            ];
            foreach ($costLines as [$label, $value, $icon]):
                if ($value <= 0) continue;
            ?>
            <div class="flex justify-between items-center py-1">
                <span class="text-xs sm:text-sm text-gray-600"><i class="fa-solid <?= $icon ?> text-xs text-gray-400 mr-1"></i><?= $label ?></span>
                <span class="font-medium text-red-600"><?= format_money($value) ?></span>
            </div>
            <?php endforeach; ?>
            <div class="flex justify-between items-center py-1.5 border-t border-gray-200 mt-1">
                <span class="text-sm font-semibold text-gray-700">Költségek összesen</span>
                <span class="font-bold text-red-600"><?= format_money($pl['costs_total']) ?><?= changeBadge($plChange['costs_total'] ?? null, true) ?></span>
            </div>
        </div>

        <!-- Eredmény -->
        <div class="<?= $pl['profit'] >= 0 ? 'bg-emerald-50 border-emerald-200' : 'bg-red-50 border-red-200' ?> rounded-xl p-4 border-2">
            <div class="flex justify-between items-center">
                <span class="font-heading font-bold text-gray-900 text-lg">Eredmény</span>
                <span class="font-heading font-extrabold text-2xl <?= $pl['profit'] >= 0 ? 'text-emerald-700' : 'text-red-700' ?>">
                    <?= $pl['profit'] >= 0 ? '+' : '' ?><?= format_money($pl['profit']) ?>
                </span>
            </div>
            <?php if ($plChange['profit'] !== null): ?>
            <p class="text-xs text-gray-500 mt-1">
                Előző időszakhoz képest: <?= changeBadge($plChange['profit']) ?>
                (előző: <?= format_money($plPrev['profit']) ?>)
            </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pénzforgalmi előrejelzés -->
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <h3 class="font-heading font-bold text-gray-900 mb-4 flex items-center gap-2">
            <i class="fa-solid fa-crystal-ball text-purple-500"></i>
            Havi előrejelzés
            <span class="text-[10px] font-normal text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full"><?= date('Y. F') ?> · <?= $fc['daysPassed'] ?? 0 ?>/<?= $fc['daysInMonth'] ?? 0 ?> nap</span>
        </h3>

        <div class="space-y-3">
            <!-- Jelenlegi pozíció -->
            <div class="bg-gray-50 rounded-xl p-3">
                <div class="flex justify-between items-center">
                    <span class="text-xs sm:text-sm text-gray-600"><i class="fa-solid fa-wallet text-gray-400 mr-1"></i>Jelenlegi likvid pozíció</span>
                    <span class="font-heading font-bold text-lg"><?= format_money($fc['currentCash'] ?? 0) ?></span>
                </div>
            </div>

            <!-- Várható bevétel -->
            <div class="flex justify-between items-center py-1.5">
                <span class="text-xs sm:text-sm text-gray-600"><i class="fa-solid fa-chart-line text-emerald-400 mr-1"></i>Vetített havi forgalom</span>
                <span class="font-medium text-emerald-600"><?= format_money($fc['projectedRevenue'] ?? 0) ?></span>
            </div>

            <!-- Várható költségek -->
            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mt-2">Várható havi költségek (3 havi átlag)</p>
            <div class="flex justify-between items-center py-1">
                <span class="text-xs sm:text-sm text-gray-600"><i class="fa-solid fa-money-bill text-gray-400 mr-1"></i>Üzleti költségek</span>
                <span class="font-medium text-red-500"><?= format_money($fc['avgMonthlyCosts'] ?? 0) ?></span>
            </div>
            <div class="flex justify-between items-center py-1">
                <span class="text-xs sm:text-sm text-gray-600"><i class="fa-solid fa-building text-gray-400 mr-1"></i>Szolgáltatók</span>
                <span class="font-medium text-red-500"><?= format_money($fc['avgMonthlyProviders'] ?? 0) ?></span>
            </div>
            <div class="flex justify-between items-center py-1">
                <span class="text-xs sm:text-sm text-gray-600"><i class="fa-solid fa-landmark text-gray-400 mr-1"></i>Hiteltörlesztés</span>
                <span class="font-medium text-red-500"><?= format_money($fc['avgMonthlyLoan'] ?? 0) ?></span>
            </div>
            <div class="flex justify-between items-center py-1">
                <span class="text-xs sm:text-sm text-gray-600"><i class="fa-solid fa-receipt text-gray-400 mr-1"></i>Várható ÁFA kötelezettség</span>
                <span class="font-medium text-red-500"><?= format_money($fc['projectedVat'] ?? 0) ?></span>
            </div>

            <!-- Becsült szabad pénz -->
            <?php $free = $fc['freeCashEstimate'] ?? 0; ?>
            <div class="<?= $free >= 0 ? 'bg-emerald-50 border-emerald-200' : 'bg-red-50 border-red-200' ?> rounded-xl p-4 border-2 mt-2">
                <div class="flex justify-between items-center">
                    <div>
                        <span class="font-heading font-bold text-gray-900">Becsült szabad pénz</span>
                        <p class="text-[10px] text-gray-500">hónap végén (ÁFA nélkül)</p>
                    </div>
                    <span class="font-heading font-extrabold text-xl <?= $free >= 0 ? 'text-emerald-700' : 'text-red-700' ?>">
                        <?= format_money($free) ?>
                    </span>
                </div>
            </div>

            <?php if (($fc['totalLoan'] ?? 0) < 0): ?>
            <div class="bg-amber-50 rounded-xl p-3 border border-amber-200 mt-2">
                <div class="flex items-start gap-2">
                    <i class="fa-solid fa-landmark text-amber-600 mt-0.5"></i>
                    <div>
                        <p class="text-xs font-bold text-amber-800">Összes hiteltartozás</p>
                        <p class="text-lg font-heading font-bold text-amber-700"><?= format_money(abs($fc['totalLoan'])) ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Össz pénzügyi helyzet -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <!-- Kasszák -->
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <p class="text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-3">
            <i class="fa-solid fa-vault text-amber-500 mr-1"></i>Kasszák összesen
        </p>
        <p class="text-2xl font-heading font-extrabold <?= ($data['totalKassza'] ?? 0) >= 0 ? 'text-emerald-600' : 'text-red-600' ?>">
            <?= format_money($data['totalKassza'] ?? 0) ?>
        </p>
        <div class="mt-3 space-y-1">
            <?php foreach ($kasszak as $k): ?>
            <div class="flex justify-between text-sm">
                <span class="text-gray-500"><?= e($k['name']) ?></span>
                <span class="font-medium"><?= format_money($k['egyenleg']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Bankszámlák -->
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <p class="text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-3">
            <i class="fa-solid fa-building-columns text-blue-500 mr-1"></i>Bankszámlák összesen
        </p>
        <p class="text-2xl font-heading font-extrabold <?= ($data['totalBank'] ?? 0) >= 0 ? 'text-emerald-600' : 'text-red-600' ?>">
            <?= format_money($data['totalBank'] ?? 0) ?>
        </p>
        <div class="mt-3 space-y-1">
            <?php foreach ($bankAccounts as $b): ?>
            <div class="flex justify-between text-sm">
                <span class="text-gray-500"><?= e($b['name']) ?></span>
                <span class="font-medium <?= $b['balance'] >= 0 ? '' : 'text-red-600' ?>">
                    <?php if ($b['currency'] !== 'HUF'): ?>
                        <?= e(\App\Core\ExchangeRate::formatWithHuf($b['balance'], $b['currency'])) ?>
                    <?php else: ?>
                        <?= format_money($b['balance']) ?>
                    <?php endif; ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Készpénz összesen -->
    <div class="bg-gradient-to-br from-[#0b0f0e] to-[#1a1f1e] rounded-2xl shadow-sm p-5 text-white">
        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">
            <i class="fa-solid fa-coins text-accent mr-1"></i>Készpénz összesen
        </p>
        <p class="text-3xl font-heading font-extrabold text-accent">
            <?= format_money($data['totalCash'] ?? 0) ?>
        </p>
        <p class="text-xs text-gray-400 mt-1">Kasszák + bankszámlák (hitelkártya nélkül)</p>

        <div class="mt-4 pt-3 border-t border-white/10 space-y-2">
            <div class="flex justify-between text-sm">
                <span class="text-gray-400">Össz pozíció (bankokkal)</span>
                <span class="font-bold text-white"><?= format_money(($data['totalKassza'] ?? 0) + ($data['totalBank'] ?? 0)) ?></span>
            </div>

            <?php if (!empty($loanAccounts)): ?>
            <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mt-2">Hitelek</p>
            <?php foreach ($loanAccounts as $l): ?>
            <div class="flex justify-between text-sm">
                <span class="text-gray-400"><?= e($l['name']) ?></span>
                <span class="font-medium text-red-400"><?= format_money($l['balance']) ?></span>
            </div>
            <?php endforeach; ?>
            <div class="flex justify-between text-sm pt-2 border-t border-white/10">
                <span class="text-gray-300 font-bold">Hitelekkel együtt</span>
                <span class="font-bold text-white"><?= format_money(($data['totalKassza'] ?? 0) + ($data['totalBank'] ?? 0) + ($data['totalLoan'] ?? 0)) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ÁFA kalkuláció (előző hónap) -->
<div class="bg-white rounded-2xl shadow-sm p-5 mb-6">
    <h3 class="font-heading font-bold text-gray-900 mb-4 flex items-center gap-2">
        <i class="fa-solid fa-receipt text-purple-500"></i>
        ÁFA kalkuláció — <?= e($data['prevMonthName'] ?? '') ?>
    </h3>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-gray-50 rounded-xl p-4">
            <p class="text-[10px] font-bold text-gray-500 uppercase mb-1">Bruttó forgalom</p>
            <p class="text-lg font-heading font-bold"><?= format_money($data['prevRevenueBrutto'] ?? 0) ?></p>
        </div>
        <div class="bg-red-50 rounded-xl p-4">
            <p class="text-[10px] font-bold text-red-500 uppercase mb-1">Fizetendő ÁFA (27%)</p>
            <p class="text-lg font-heading font-bold text-red-600"><?= format_money($data['vatPayable'] ?? 0) ?></p>
        </div>
        <div class="bg-emerald-50 rounded-xl p-4">
            <p class="text-[10px] font-bold text-emerald-500 uppercase mb-1">Levonható ÁFA (számlákból)</p>
            <p class="text-lg font-heading font-bold text-emerald-600"><?= format_money($data['vatDeductible'] ?? 0) ?></p>
        </div>
        <div class="bg-purple-50 rounded-xl p-4 border-2 border-purple-200">
            <p class="text-[10px] font-bold text-purple-600 uppercase mb-1">Befizetendő ÁFA</p>
            <p class="text-xl font-heading font-extrabold text-purple-700"><?= format_money($data['vatNet'] ?? 0) ?></p>
        </div>
    </div>
</div>

<!-- Fizetések összesítő -->
<?php
$salaryByEmployee = $data['salaryByEmployee'] ?? [];
$ownerPayments = $data['ownerPayments'] ?? [];
$salaryTotal = $data['salaryTotal'] ?? 0;
$ownerTotal = $data['ownerTotal'] ?? 0;
?>
<?php if (!empty($salaryByEmployee) || !empty($ownerPayments)): ?>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <!-- Dolgozói fizetések -->
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <h3 class="font-heading font-bold text-gray-900 mb-3 flex items-center gap-2">
            <i class="fa-solid fa-hand-holding-dollar text-blue-500"></i>
            Dolgozói fizetések
        </h3>
        <?php if (empty($salaryByEmployee)): ?>
            <p class="text-sm text-gray-400">Nincs fizetés az időszakban.</p>
        <?php else: ?>
            <div class="space-y-1.5">
                <?php foreach ($salaryByEmployee as $s): ?>
                <div class="flex justify-between items-center py-1 text-sm">
                    <span class="text-gray-600"><?= e($s['name']) ?></span>
                    <span class="font-medium"><?= format_money($s['total']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="flex justify-between items-center pt-3 mt-3 border-t border-gray-200">
                <span class="font-heading font-bold text-gray-900">Összesen</span>
                <span class="font-heading font-bold text-lg"><?= format_money($salaryTotal) ?></span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tulajdonosi kifizetések -->
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <h3 class="font-heading font-bold text-gray-900 mb-3 flex items-center gap-2">
            <i class="fa-solid fa-user-tie text-amber-500"></i>
            Tulajdonosi kifizetések
        </h3>
        <?php if (empty($ownerPayments)): ?>
            <p class="text-sm text-gray-400">Nincs tulajdonosi kifizetés az időszakban.</p>
        <?php else: ?>
            <div class="space-y-1.5">
                <?php foreach ($ownerPayments as $o): ?>
                <div class="flex justify-between items-center py-1 text-sm">
                    <span class="text-gray-600"><?= e($o['owner_name']) ?></span>
                    <span class="font-medium"><?= format_money($o['total']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="flex justify-between items-center pt-3 mt-3 border-t border-gray-200">
                <span class="font-heading font-bold text-gray-900">Összesen</span>
                <span class="font-heading font-bold text-lg"><?= format_money($ownerTotal) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($salaryTotal + $ownerTotal > 0): ?>
        <div class="flex justify-between items-center pt-3 mt-3 border-t border-gray-300 bg-gray-50 -mx-5 -mb-5 px-5 py-3 rounded-b-2xl">
            <span class="font-heading font-bold text-gray-900">Mind összesen</span>
            <span class="font-heading font-extrabold text-lg text-red-600"><?= format_money($salaryTotal + $ownerTotal) ?></span>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Dátum szűrő -->
<div class="bg-white rounded-2xl shadow-sm p-3 sm:p-6 mb-4 sm:mb-6">
    <form method="GET" action="<?= base_url('/finance/summary') ?>" class="flex flex-wrap gap-2 sm:gap-3 items-end">
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Időszak kezdete</label>
            <input type="date" name="date_from" value="<?= e($dateFrom) ?>" class="px-3 py-2 border border-gray-300 rounded-xl text-sm focus:ring-primary/50">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Időszak vége</label>
            <input type="date" name="date_to" value="<?= e($dateTo) ?>" class="px-3 py-2 border border-gray-300 rounded-xl text-sm focus:ring-primary/50">
        </div>
        <button type="submit" class="px-4 py-2 bg-sidebar text-primary rounded-xl text-sm font-bold hover:bg-gray-800 transition-colors">Szűrés</button>
    </form>
</div>

<!-- Boltonkénti összesítő -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-<?= min(count($summary), 3) ?> gap-4 sm:gap-6">
    <?php foreach ($summary as $store): ?>
    <div class="bg-white rounded-2xl shadow-sm p-3 sm:p-6 overflow-hidden">
        <h3 class="font-heading font-bold text-gray-900 mb-3 sm:mb-4 flex items-center gap-2 text-sm sm:text-base">
            <i class="fa-solid fa-store text-primary"></i>
            <?= e($store['name']) ?>
        </h3>
        <div class="space-y-1">
            <!-- Bevételek -->
            <p class="text-[10px] font-bold uppercase tracking-widest text-emerald-600 mt-1 mb-1"><i class="fa-solid fa-arrow-down text-[8px] mr-0.5"></i>Bevételek</p>
            <div class="flex justify-between items-center py-1.5 border-b border-gray-100">
                <span class="text-xs sm:text-sm text-gray-600"><i class="fa-solid fa-money-bill text-xs text-gray-400 mr-1.5"></i>Készpénz forgalom</span>
                <span class="font-medium"><?= format_money($store['keszpenz']) ?></span>
            </div>
            <div class="flex justify-between items-center py-1.5 border-b border-gray-100">
                <span class="text-xs sm:text-sm text-gray-600"><i class="fa-solid fa-credit-card text-xs text-gray-400 mr-1.5"></i>Bankkártya forgalom</span>
                <span class="font-medium"><?= format_money($store['bankkartya']) ?></span>
            </div>
            <div class="flex justify-between items-center py-1.5 border-b border-gray-100">
                <span class="text-xs sm:text-sm text-gray-600"><i class="fa-solid fa-building-columns text-xs text-gray-400 mr-1.5"></i>Befizetés bankból</span>
                <span class="font-medium text-emerald-600"><?= format_money($store['befizetes_bankbol']) ?></span>
            </div>
            <div class="flex justify-between items-center py-1.5 border-b border-gray-100">
                <span class="text-xs sm:text-sm text-gray-600"><i class="fa-solid fa-store text-xs text-gray-400 mr-1.5"></i>Befizetés másik boltból</span>
                <span class="font-medium text-emerald-600"><?= format_money($store['befizetes_boltbol']) ?></span>
            </div>
            <div class="flex justify-between items-center py-1.5 border-b border-gray-100">
                <span class="text-xs sm:text-sm text-gray-600"><i class="fa-solid fa-box-open text-xs text-gray-400 mr-1.5"></i>Selejt befizetés</span>
                <span class="font-medium text-emerald-600"><?= format_money($store['selejt_befizetes']) ?></span>
            </div>
            <div class="flex justify-between items-center py-1.5 border-b border-gray-100">
                <span class="text-xs sm:text-sm text-gray-600"><i class="fa-solid fa-box-open text-xs text-gray-400 mr-1.5"></i>Selejt összérték</span>
                <span class="font-medium text-orange-600"><?= format_money($store['selejt']) ?></span>
            </div>
            <div class="flex justify-between items-center py-1.5 border-b border-gray-100">
                <span class="text-xs sm:text-sm text-gray-600"><i class="fa-solid fa-cash-register text-xs text-gray-400 mr-1.5"></i>Kassza nyitó</span>
                <span class="font-medium"><?= format_money($store['kassza_nyito']) ?></span>
            </div>

            <!-- Kiadások -->
            <p class="text-[10px] font-bold uppercase tracking-widest text-red-500 mt-3 mb-1"><i class="fa-solid fa-arrow-up text-[8px] mr-0.5"></i>Kiadások</p>
            <div class="flex justify-between items-center py-1.5 border-b border-gray-100">
                <span class="text-xs sm:text-sm text-gray-600"><i class="fa-solid fa-hand-holding-dollar text-xs text-gray-400 mr-1.5"></i>Munkabérek</span>
                <span class="font-medium text-red-600"><?= format_money($store['munkaber']) ?></span>
            </div>
            <div class="flex justify-between items-center py-1.5 border-b border-gray-100">
                <span class="text-xs sm:text-sm text-gray-600"><i class="fa-solid fa-building-columns text-xs text-gray-400 mr-1.5"></i>Bank kifizetés</span>
                <span class="font-medium text-red-600"><?= format_money($store['bank_kifizetes']) ?></span>
            </div>
            <div class="flex justify-between items-center py-1.5 border-b border-gray-100">
                <span class="text-xs sm:text-sm text-gray-600"><i class="fa-solid fa-file-invoice text-xs text-gray-400 mr-1.5"></i>Számla kifizetés</span>
                <span class="font-medium text-red-600"><?= format_money($store['szamla_kifizetes']) ?></span>
            </div>
            <div class="flex justify-between items-center py-1.5 border-b border-gray-100">
                <span class="text-xs sm:text-sm text-gray-600"><i class="fa-solid fa-file-invoice-dollar text-xs text-gray-400 mr-1.5"></i>Egyéb kiadások</span>
                <span class="font-medium text-red-600"><?= format_money($store['egyeb_kiadasok']) ?></span>
            </div>
            <div class="flex justify-between items-center py-1.5 border-b border-gray-200">
                <span class="text-sm font-semibold text-gray-700">Kiadások összesen</span>
                <span class="font-bold text-red-600"><?= format_money($store['kiadasok']) ?></span>
            </div>

            <!-- Összesen -->
            <div class="flex justify-between items-center py-3 bg-surface rounded-xl px-3 mt-2">
                <span class="font-heading font-bold text-gray-900">Összesen</span>
                <span class="font-heading font-bold text-lg"><?= format_money($store['total']) ?></span>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
