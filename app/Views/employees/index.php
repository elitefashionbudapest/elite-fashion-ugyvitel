<div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8">
    <div>
        <h1 class="text-3xl font-heading font-extrabold text-on-surface tracking-tight mb-1">Dolgozók</h1>
        <p class="text-on-surface-variant text-sm">Az Elite Fashion alkalmazottak kezelése és bolt hozzárendelés.</p>
    </div>
    <a href="<?= base_url('/employees/create') ?>" class="px-6 py-3 bg-gradient-to-br from-primary to-primary-container text-on-primary-fixed font-bold rounded-full flex items-center gap-2 shadow-lg shadow-primary/10 hover:shadow-primary/20 transition-all text-sm">
        <i class="fa-solid fa-user-plus"></i> Új dolgozó
    </a>
</div>

<!-- Stats -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-surface-container-low p-4 rounded-lg">
        <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest mb-1 block">Összes dolgozó</span>
        <span class="text-2xl font-extrabold text-on-surface"><?= count($data['employees']) ?></span>
    </div>
    <div class="bg-primary-container/30 p-4 rounded-lg">
        <span class="text-[10px] font-bold text-on-primary-container uppercase tracking-widest mb-1 block">Aktív</span>
        <span class="text-2xl font-extrabold text-on-primary-container"><?= count(array_filter($data['employees'], fn($e) => $e['is_active'])) ?></span>
    </div>
</div>

<!-- Table -->
<div class="bg-surface-container-lowest rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-surface-container-low">
                    <th class="px-8 py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Dolgozó</th>
                    <th class="px-6 py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Boltok</th>
                    <th class="px-6 py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Státusz</th>
                    <th class="px-8 py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest text-right">Műveletek</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-container">
                <?php if (empty($data['employees'])): ?>
                    <tr><td colspan="4" class="px-8 py-12 text-center text-on-surface-variant">
                        <i class="fa-solid fa-users text-4xl mb-2 block text-outline-variant"></i>
                        Nincs még dolgozó felvéve.
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($data['employees'] as $emp): ?>
                    <tr class="hover:bg-surface-container-low/50 transition-colors">
                        <td class="px-8 py-5">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-full bg-secondary-container flex items-center justify-center text-on-secondary-container font-bold text-sm">
                                    <?= e(mb_substr($emp['name'], 0, 2)) ?>
                                </div>
                                <span class="font-bold text-on-surface"><?= e($emp['name']) ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-5 text-sm font-medium text-on-surface-variant"><?= e($emp['store_names'] ?? '—') ?></td>
                        <td class="px-6 py-5">
                            <?php if ($emp['is_active']): ?>
                                <div class="flex items-center gap-2 text-primary font-semibold text-xs">
                                    <span class="w-2 h-2 bg-primary rounded-full"></span> Aktív
                                </div>
                            <?php else: ?>
                                <div class="flex items-center gap-2 text-on-surface-variant font-semibold text-xs">
                                    <span class="w-2 h-2 bg-surface-variant rounded-full border border-outline-variant"></span> Inaktív
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-8 py-5 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="<?= base_url("/employees/{$emp['id']}/edit") ?>" class="p-2 hover:bg-surface-container rounded-full transition-colors text-on-surface-variant">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <form method="POST" action="<?= base_url("/employees/{$emp['id']}/delete") ?>" class="inline" onsubmit="return confirmDelete(this, '<?= e($emp['name']) ?>')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="p-2 hover:bg-error-container/10 rounded-full transition-colors text-on-surface-variant hover:text-error">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
