<?php
use App\Core\Auth;
use App\Models\{SalaryPayment, OwnerPayment};

$records = $data['records'] ?? [];
$ownerRecords = $data['ownerRecords'] ?? [];
$employees = $data['employees'] ?? [];
$filters = $data['filters'] ?? [];
$tab = $data['tab'] ?? 'dolgozoi';

$months = [
    1 => 'Január', 2 => 'Február', 3 => 'Március',
    4 => 'Április', 5 => 'Május', 6 => 'Június',
    7 => 'Július', 8 => 'Augusztus', 9 => 'Szeptember',
    10 => 'Október', 11 => 'November', 12 => 'December',
];
?>

<div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8">
    <div>
        <h1 class="text-3xl font-heading font-extrabold text-on-surface tracking-tight mb-1">Fizetések</h1>
        <p class="text-on-surface-variant text-sm">Dolgozói és tulajdonosi fizetések rögzítése.</p>
    </div>
    <a href="<?= base_url('/salary/create?type=' . $tab) ?>" class="px-6 py-3 bg-gradient-to-br from-primary to-primary-container text-on-primary-fixed font-bold rounded-full flex items-center gap-2 shadow-lg shadow-primary/10 hover:shadow-primary/20 transition-all text-sm">
        <i class="fa-solid fa-plus"></i> Új rögzítés
    </a>
</div>

<?php $isOwner = Auth::isOwner(); ?>
<!-- Fül váltó -->
<?php if ($isOwner): ?>
<div class="flex gap-1 bg-surface-container-low p-1 rounded-lg w-fit mb-6">
    <a href="<?= base_url('/salary?tab=dolgozoi') ?>"
       class="px-5 py-2.5 rounded-lg text-sm font-semibold transition-all <?= $tab === 'dolgozoi' ? 'bg-surface-container-lowest text-on-surface shadow-sm' : 'text-on-surface-variant hover:text-on-surface' ?>">
        <i class="fa-solid fa-users text-sm align-middle mr-1"></i>
        Dolgozói
    </a>
    <a href="<?= base_url('/salary?tab=tulajdonosi') ?>"
       class="px-5 py-2.5 rounded-lg text-sm font-semibold transition-all <?= $tab === 'tulajdonosi' ? 'bg-surface-container-lowest text-on-surface shadow-sm' : 'text-on-surface-variant hover:text-on-surface' ?>">
        <i class="fa-solid fa-user-shield text-sm align-middle mr-1"></i>
        Tulajdonosi
    </a>
</div>
<?php endif; ?>

<?php if ($tab === 'dolgozoi'): ?>
<!-- ===================== DOLGOZÓI FIZETÉSEK ===================== -->

<!-- Szűrők -->
<div class="bg-surface-container-low p-4 rounded-lg flex flex-wrap items-center gap-4 mb-6">
    <span class="text-xs font-bold text-on-surface-variant uppercase tracking-widest">Szűrők</span>
    <form method="GET" action="<?= base_url('/salary') ?>" class="flex flex-wrap gap-3 items-center flex-1">
        <input type="hidden" name="tab" value="dolgozoi">
        <select name="employee_id" class="bg-surface-container-lowest border-none rounded-full text-xs font-semibold py-2 px-4 focus:ring-2 focus:ring-primary-container cursor-pointer">
            <option value="">Minden dolgozó</option>
            <?php foreach ($employees as $emp): ?>
                <option value="<?= $emp['id'] ?>" <?= ($filters['employee_id'] ?? '') == $emp['id'] ? 'selected' : '' ?>><?= e($emp['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="year" class="bg-surface-container-lowest border-none rounded-full text-xs font-semibold py-2 px-4 focus:ring-2 focus:ring-primary-container cursor-pointer">
            <option value="">Minden év</option>
            <?php for ($y = 2030; $y >= 2017; $y--): ?>
                <option value="<?= $y ?>" <?= ($filters['year'] ?? '') == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
        <select name="month" class="bg-surface-container-lowest border-none rounded-full text-xs font-semibold py-2 px-4 focus:ring-2 focus:ring-primary-container cursor-pointer">
            <option value="">Minden hónap</option>
            <?php foreach ($months as $num => $name): ?>
                <option value="<?= $num ?>" <?= ($filters['month'] ?? '') == $num ? 'selected' : '' ?>><?= e($name) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="px-5 py-2 bg-secondary-container text-on-secondary-container font-semibold rounded-full text-xs hover:bg-surface-variant transition-colors">Szűrés</button>
    </form>
</div>

<!-- Táblázat -->
<div class="bg-surface-container-lowest rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-surface-container-low">
                    <th class="px-8 py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Dolgozó</th>
                    <th class="px-6 py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Időszak</th>
                    <th class="px-6 py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Honnan fizetve</th>
                    <th class="px-6 py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest text-right">Összeg</th>
                    <th class="px-8 py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest text-right">Műveletek</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-container">
                <?php if (empty($records)): ?>
                    <tr><td colspan="5" class="px-8 py-12 text-center text-on-surface-variant">
                        <i class="fa-solid fa-money-bill-wave text-4xl mb-2 block text-outline-variant"></i>
                        Nincs találat.
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($records as $r): ?>
                    <tr class="hover:bg-surface-container-low/50 transition-colors">
                        <td class="px-8 py-5">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-full bg-secondary-container flex items-center justify-center text-on-secondary-container font-bold text-sm">
                                    <?= e(mb_substr($r['employee_name'], 0, 2)) ?>
                                </div>
                                <span class="font-bold text-on-surface"><?= e($r['employee_name']) ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-5 text-sm font-medium text-on-surface"><?= (int)$r['year'] ?>. <?= e($months[(int)$r['month']] ?? $r['month']) ?></td>
                        <td class="px-6 py-5">
                            <span class="px-3 py-1 bg-surface-container text-on-surface text-[10px] font-bold rounded-full"><?= e(SalaryPayment::SOURCES[$r['source']] ?? $r['source']) ?></span>
                        </td>
                        <td class="px-6 py-5 text-right font-bold text-on-surface whitespace-nowrap"><?= format_money($r['amount']) ?></td>
                        <td class="px-8 py-5 text-right">
                            <form method="POST" action="<?= base_url("/salary/{$r['id']}/delete") ?>" class="inline" onsubmit="return confirm('Biztosan törli?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="type" value="dolgozoi">
                                <button type="submit" class="p-2 hover:bg-error-container/10 rounded-full transition-colors text-on-surface-variant hover:text-error">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (!empty($records)): ?>
    <div class="bg-surface-container-low px-8 py-4 border-t border-surface-container">
        <p class="text-xs font-medium text-on-surface-variant"><?= count($records) ?> bejegyzés</p>
    </div>
    <?php endif; ?>
</div>

<?php else: ?>
<!-- ===================== TULAJDONOSI FIZETÉSEK ===================== -->

<!-- Szűrők -->
<div class="bg-surface-container-low p-4 rounded-lg flex flex-wrap items-center gap-4 mb-6">
    <span class="text-xs font-bold text-on-surface-variant uppercase tracking-widest">Szűrők</span>
    <form method="GET" action="<?= base_url('/salary') ?>" class="flex flex-wrap gap-3 items-center flex-1">
        <input type="hidden" name="tab" value="tulajdonosi">
        <select name="owner_name" class="bg-surface-container-lowest border-none rounded-full text-xs font-semibold py-2 px-4 focus:ring-2 focus:ring-primary-container cursor-pointer">
            <option value="">Minden tulajdonos</option>
            <?php foreach (OwnerPayment::OWNERS as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= ($filters['owner_name'] ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="year" class="bg-surface-container-lowest border-none rounded-full text-xs font-semibold py-2 px-4 focus:ring-2 focus:ring-primary-container cursor-pointer">
            <option value="">Minden év</option>
            <?php for ($y = 2030; $y >= 2017; $y--): ?>
                <option value="<?= $y ?>" <?= ($filters['year'] ?? '') == $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
        <select name="month" class="bg-surface-container-lowest border-none rounded-full text-xs font-semibold py-2 px-4 focus:ring-2 focus:ring-primary-container cursor-pointer">
            <option value="">Minden hónap</option>
            <?php foreach ($months as $num => $name): ?>
                <option value="<?= $num ?>" <?= ($filters['month'] ?? '') == $num ? 'selected' : '' ?>><?= e($name) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="px-5 py-2 bg-secondary-container text-on-secondary-container font-semibold rounded-full text-xs hover:bg-surface-variant transition-colors">Szűrés</button>
    </form>
</div>

<!-- Táblázat -->
<div class="bg-surface-container-lowest rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-surface-container-low">
                    <th class="px-8 py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Tulajdonos</th>
                    <th class="px-6 py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Időszak</th>
                    <th class="px-6 py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Honnan fizetve</th>
                    <th class="px-6 py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest text-right">Összeg</th>
                    <th class="px-8 py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest text-right">Műveletek</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-container">
                <?php if (empty($ownerRecords)): ?>
                    <tr><td colspan="5" class="px-8 py-12 text-center text-on-surface-variant">
                        <i class="fa-solid fa-user-shield text-4xl mb-2 block text-outline-variant"></i>
                        Nincs találat.
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($ownerRecords as $r): ?>
                    <tr class="hover:bg-surface-container-low/50 transition-colors">
                        <td class="px-8 py-5">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-full bg-tertiary-container flex items-center justify-center text-on-tertiary-container font-bold text-sm">
                                    <?= e(mb_substr($r['owner_name'], 0, 2)) ?>
                                </div>
                                <div>
                                    <span class="font-bold text-on-surface"><?= e($r['owner_name']) ?></span>
                                    <span class="block text-[10px] text-on-surface-variant uppercase font-bold tracking-wider">Tulajdonos</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-5 text-sm font-medium text-on-surface"><?= (int)$r['year'] ?>. <?= e($months[(int)$r['month']] ?? $r['month']) ?></td>
                        <td class="px-6 py-5">
                            <span class="px-3 py-1 bg-surface-container text-on-surface text-[10px] font-bold rounded-full"><?= e(OwnerPayment::SOURCES[$r['payment_source']] ?? $r['payment_source']) ?></span>
                        </td>
                        <td class="px-6 py-5 text-right font-bold text-on-surface whitespace-nowrap"><?= format_money($r['amount']) ?></td>
                        <td class="px-8 py-5 text-right">
                            <form method="POST" action="<?= base_url("/salary/{$r['id']}/delete") ?>" class="inline" onsubmit="return confirm('Biztosan törli?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="type" value="tulajdonosi">
                                <button type="submit" class="p-2 hover:bg-error-container/10 rounded-full transition-colors text-on-surface-variant hover:text-error">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (!empty($ownerRecords)): ?>
    <div class="bg-surface-container-low px-8 py-4 border-t border-surface-container">
        <p class="text-xs font-medium text-on-surface-variant"><?= count($ownerRecords) ?> bejegyzés</p>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>
