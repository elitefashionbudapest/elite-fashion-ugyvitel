<?php
use App\Core\Auth;
use App\Models\FinancialRecord;

$records = $data['records'] ?? [];
$stores = $data['stores'] ?? [];
$filters = $data['filters'] ?? [];
?>

<div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8">
    <div>
        <h1 class="text-3xl font-heading font-extrabold text-on-surface tracking-tight mb-1">Könyvelés</h1>
        <p class="text-on-surface-variant text-sm">Pénzmozgások rögzítése és áttekintése.</p>
    </div>
    <a href="<?= base_url('/finance/create') ?>" class="px-6 py-3 bg-gradient-to-br from-primary to-primary-container text-on-primary-fixed font-bold rounded-full flex items-center gap-2 shadow-lg shadow-primary/10 hover:shadow-primary/20 transition-all text-sm">
        <i class="fa-solid fa-plus"></i> Új rögzítés
    </a>
</div>

<!-- Szűrők -->
<div class="bg-surface-container-low p-4 rounded-lg flex flex-wrap items-center gap-4 mb-6">
    <span class="text-xs font-bold text-on-surface-variant uppercase tracking-widest">Szűrők</span>
    <form method="GET" action="<?= base_url('/finance') ?>" class="flex flex-wrap gap-3 items-center flex-1">
        <?php if (Auth::isOwner()): ?>
        <select name="store_id" class="bg-surface-container-lowest border-none rounded-full text-xs font-semibold py-2 px-4 focus:ring-2 focus:ring-primary-container cursor-pointer">
            <option value="">Minden bolt</option>
            <?php foreach ($stores as $s): ?>
                <option value="<?= $s['id'] ?>" <?= ($filters['store_id'] ?? '') == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <input type="date" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>" class="bg-surface-container-lowest border-none rounded-full text-xs font-semibold py-2 px-4 focus:ring-2 focus:ring-primary-container">
        <input type="date" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>" class="bg-surface-container-lowest border-none rounded-full text-xs font-semibold py-2 px-4 focus:ring-2 focus:ring-primary-container">
        <select name="purpose" class="bg-surface-container-lowest border-none rounded-full text-xs font-semibold py-2 px-4 focus:ring-2 focus:ring-primary-container cursor-pointer">
            <option value="">Minden típus</option>
            <?php foreach (FinancialRecord::PURPOSES as $key => $label): ?>
                <option value="<?= $key ?>" <?= ($filters['purpose'] ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="px-5 py-2 bg-secondary-container text-on-secondary-container font-semibold rounded-full text-xs hover:bg-surface-variant transition-colors">Szűrés</button>
        <a href="<?= base_url('/finance') ?>" class="text-xs text-on-surface-variant hover:text-on-surface font-medium">Törlés</a>
    </form>
</div>

<!-- Táblázat -->
<div class="bg-surface-container-lowest rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-surface-container-low">
                    <th class="px-6 py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Dátum</th>
                    <th class="px-6 py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest hide-mobile">Bolt</th>
                    <th class="px-6 py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Típus</th>
                    <th class="px-6 py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest text-right">Összeg</th>
                    <th class="px-6 py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest hide-mobile">Megjegyzés</th>
                    <th class="px-6 py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest hide-mobile">Rögzítette</th>
                    <th class="px-6 py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest text-right">Műveletek</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-container">
                <?php if (empty($records)): ?>
                    <tr><td colspan="7" class="px-8 py-12 text-center text-on-surface-variant">
                        <i class="fa-solid fa-building-columns text-4xl mb-2 block text-outline-variant"></i>
                        Nincs találat.
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($records as $r): ?>
                    <tr class="hover:bg-surface-container-low/50 transition-colors">
                        <td class="px-6 py-5 text-sm font-medium text-on-surface whitespace-nowrap"><?= date('Y.m.d', strtotime($r['record_date'])) ?></td>
                        <td class="px-6 py-5 text-sm text-on-surface-variant hide-mobile"><?= e($r['store_name']) ?></td>
                        <td class="px-6 py-5">
                            <span class="px-3 py-1 bg-surface-container text-on-surface text-[10px] font-bold rounded-full"><?= e(FinancialRecord::PURPOSES[$r['purpose']] ?? $r['purpose']) ?></span>
                        </td>
                        <td class="px-6 py-5 text-right font-bold text-on-surface whitespace-nowrap"><?= format_money($r['amount']) ?></td>
                        <td class="px-6 py-5 text-sm text-on-surface-variant max-w-[200px] truncate hide-mobile">
                            <?= e($r['paid_to_name'] ? "→ {$r['paid_to_name']}" : ($r['description'] ?? '')) ?>
                        </td>
                        <td class="px-6 py-5 text-sm text-on-surface-variant hide-mobile"><?= e($r['recorded_by_name']) ?></td>
                        <td class="px-6 py-5 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="<?= base_url("/finance/{$r['id']}/edit") ?>" class="p-2 hover:bg-surface-container rounded-full transition-colors text-on-surface-variant">
                                    <i class="fa-solid fa-pen-to-square text-lg"></i>
                                </a>
                                <?php if (Auth::isOwner()): ?>
                                <form method="POST" action="<?= base_url("/finance/{$r['id']}/delete") ?>" class="inline" onsubmit="return confirm('Biztosan törli?')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="p-2 hover:bg-error-container/10 rounded-full transition-colors text-on-surface-variant hover:text-error">
                                        <i class="fa-solid fa-trash text-lg"></i>
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
    <!-- Table Footer -->
    <?php if (!empty($records)):
        $totalAmount = array_sum(array_column($records, 'amount'));
        $bevetelek = ['napi_keszpenz','napi_bankkartya','befizetes_bankbol','befizetes_boltbol','kassza_nyito','selejt_befizetes'];
        $totalBev = 0; $totalKiad = 0;
        foreach ($records as $r) {
            if (in_array($r['purpose'], $bevetelek)) $totalBev += (float)$r['amount'];
            else $totalKiad += (float)$r['amount'];
        }
    ?>
    <div class="bg-surface-container-low px-3 sm:px-8 py-3 sm:py-4 border-t border-surface-container">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="text-xs font-medium text-on-surface-variant"><?= count($records) ?> bejegyzés</p>
            <div class="flex flex-wrap items-center gap-3 sm:gap-5">
                <div class="text-right">
                    <p class="text-[10px] text-emerald-500 font-bold uppercase">Bevételek</p>
                    <p class="text-sm font-heading font-bold text-emerald-600"><?= format_money($totalBev) ?></p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] text-red-500 font-bold uppercase">Kiadások</p>
                    <p class="text-sm font-heading font-bold text-red-600"><?= format_money($totalKiad) ?></p>
                </div>
                <div class="text-right pl-3 sm:pl-5 border-l border-surface-container">
                    <p class="text-[10px] text-on-surface-variant font-bold uppercase">Összesen</p>
                    <p class="text-sm font-heading font-extrabold <?= ($totalBev - $totalKiad) >= 0 ? 'text-emerald-700' : 'text-red-700' ?>"><?= format_money($totalBev - $totalKiad) ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
