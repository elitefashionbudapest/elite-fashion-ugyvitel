<?php
$holidays = $data['holidays'] ?? [];
$year = $data['year'] ?? date('Y');
$years = $data['years'] ?? [date('Y')];
$dayNames = ['Vasárnap','Hétfő','Kedd','Szerda','Csütörtök','Péntek','Szombat'];
?>

<div class="flex flex-wrap flex-col md:flex-row md:items-end justify-between gap-4 sm:gap-6 mb-6 sm:mb-8">
    <div>
        <h1 class="text-3xl font-heading font-extrabold text-on-surface tracking-tight mb-1">Ünnepnapok</h1>
        <p class="text-on-surface-variant text-sm">Pirossal jelölt ünnepnapok kezelése.</p>
    </div>
    <div class="flex gap-2 items-center">
        <?php foreach ($years as $y): ?>
        <a href="<?= base_url('/holidays?year=' . $y) ?>"
           class="px-4 py-2 rounded-full text-sm font-bold transition-colors <?= $y == $year ? 'bg-sidebar text-accent' : 'bg-surface-container text-on-surface-variant hover:bg-surface-container-high' ?>">
            <?= $y ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Hozzáadás form -->
<div class="bg-surface-container-lowest rounded-xl p-3 sm:p-5 mb-4 sm:mb-6">
    <h3 class="font-heading font-bold text-on-surface text-sm mb-3"><i class="fa-solid fa-plus text-primary mr-1"></i>Ünnepnap hozzáadása</h3>
    <form method="POST" action="<?= base_url('/holidays') ?>" class="flex flex-wrap gap-2 sm:gap-3 items-end">
        <?= csrf_field() ?>
        <div class="w-full sm:w-auto sm:flex-1 sm:min-w-[180px]">
            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1">Dátum</label>
            <input type="date" name="date" value="<?= e(old('date')) ?>" class="w-full px-3 sm:px-4 py-2.5 sm:py-3 border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary-container focus:border-primary bg-surface-container-lowest" required>
        </div>
        <div class="w-full sm:w-auto sm:flex-[2] sm:min-w-[200px]">
            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1">Ünnep neve</label>
            <input type="text" name="name" value="<?= e(old('name')) ?>" placeholder="pl. Karácsony" class="w-full px-3 sm:px-4 py-2.5 sm:py-3 border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary-container focus:border-primary bg-surface-container-lowest" required>
        </div>
        <button type="submit" class="w-full sm:w-auto px-6 py-2.5 sm:py-3 bg-gradient-to-br from-primary to-primary-container text-on-primary-fixed font-bold rounded-full text-sm shadow-lg shadow-primary/10 flex items-center justify-center gap-2">
            <i class="fa-solid fa-plus"></i> Hozzáadás
        </button>
    </form>
</div>

<!-- Ünnepnapok lista -->
<div class="bg-surface-container-lowest rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-surface-container-low">
                    <th class="px-3 sm:px-8 py-3 sm:py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Dátum</th>
                    <th class="px-2 sm:px-6 py-3 sm:py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest hide-mobile">Nap</th>
                    <th class="px-2 sm:px-6 py-3 sm:py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Neve</th>
                    <th class="px-3 sm:px-8 py-3 sm:py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest text-right">Műv.</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-container">
                <?php if (empty($holidays)): ?>
                    <tr><td colspan="4" class="px-4 sm:px-8 py-8 sm:py-12 text-center text-on-surface-variant">
                        <i class="fa-solid fa-flag text-4xl mb-2 block text-outline-variant"></i>
                        Nincs ünnepnap <?= $year ?>-ben.
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($holidays as $h): ?>
                    <tr class="hover:bg-surface-container-low/50 transition-colors">
                        <td class="px-3 sm:px-8 py-3 sm:py-5">
                            <div class="flex items-center gap-2 sm:gap-3">
                                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg bg-purple-100 flex items-center justify-center flex-shrink-0">
                                    <i class="fa-solid fa-flag text-purple-600 text-xs sm:text-base"></i>
                                </div>
                                <span class="font-bold text-on-surface text-xs sm:text-base"><?= date('Y.m.d.', strtotime($h['date'])) ?></span>
                            </div>
                        </td>
                        <td class="px-2 sm:px-6 py-3 sm:py-5 text-sm text-on-surface-variant hide-mobile"><?= $dayNames[date('w', strtotime($h['date']))] ?></td>
                        <td class="px-2 sm:px-6 py-3 sm:py-5 text-xs sm:text-sm font-medium text-on-surface"><?= e($h['name']) ?></td>
                        <td class="px-3 sm:px-8 py-3 sm:py-5 text-right">
                            <form method="POST" action="<?= base_url('/holidays/' . $h['id'] . '/delete') ?>" class="inline" onsubmit="return confirm('Biztosan törli?')">
                                <?= csrf_field() ?>
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
</div>
