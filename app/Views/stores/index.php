<div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8">
    <div>
        <h1 class="text-3xl font-heading font-extrabold text-on-surface tracking-tight mb-1">Boltok</h1>
        <p class="text-on-surface-variant text-sm">Az Elite Fashion üzletek kezelése.</p>
    </div>
    <a href="<?= base_url('/stores/create') ?>" class="px-6 py-3 bg-gradient-to-br from-primary to-primary-container text-on-primary-fixed font-bold rounded-full flex items-center gap-2 shadow-lg shadow-primary/10 hover:shadow-primary/20 transition-all text-sm">
        <i class="fa-solid fa-plus"></i> Új bolt
    </a>
</div>

<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-surface-container-low p-4 rounded-lg flex flex-col justify-center">
        <span class="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest mb-1">Összes bolt</span>
        <span class="text-2xl font-extrabold text-on-surface"><?= count($data['stores']) ?></span>
    </div>
</div>

<!-- Table -->
<div class="bg-surface-container-lowest rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-surface-container-low">
                    <th class="px-8 py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Bolt neve</th>
                    <th class="px-6 py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Létrehozva</th>
                    <th class="px-8 py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest text-right">Műveletek</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-container">
                <?php if (empty($data['stores'])): ?>
                    <tr><td colspan="3" class="px-8 py-12 text-center text-on-surface-variant">
                        <i class="fa-solid fa-store text-4xl mb-2 block text-outline-variant"></i>
                        Nincs még bolt felvéve.
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($data['stores'] as $store): ?>
                    <tr class="hover:bg-surface-container-low/50 transition-colors">
                        <td class="px-8 py-5">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-lg bg-primary-container/30 flex items-center justify-center">
                                    <i class="fa-solid fa-store text-on-primary-container"></i>
                                </div>
                                <span class="font-bold text-on-surface"><?= e($store['name']) ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-5 text-sm text-on-surface-variant"><?= date('Y.m.d', strtotime($store['created_at'])) ?></td>
                        <td class="px-8 py-5 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="<?= base_url("/stores/{$store['id']}/edit") ?>" class="p-2 hover:bg-surface-container rounded-full transition-colors text-on-surface-variant" title="Szerkesztés">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <form method="POST" action="<?= base_url("/stores/{$store['id']}/delete") ?>" class="inline" onsubmit="return confirmDelete(this, '<?= e($store['name']) ?>')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="p-2 hover:bg-error-container/10 rounded-full transition-colors text-on-surface-variant hover:text-error" title="Törlés">
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
