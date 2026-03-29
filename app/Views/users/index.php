<div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8">
    <div>
        <h1 class="text-3xl font-heading font-extrabold text-on-surface tracking-tight mb-1">Bejelentkezési fiókok</h1>
        <p class="text-on-surface-variant text-sm">Tulajdonosi és bolt fiókok kezelése.</p>
    </div>
    <a href="<?= base_url('/users/create') ?>" class="px-6 py-3 bg-gradient-to-br from-primary to-primary-container text-on-primary-fixed font-bold rounded-full flex items-center gap-2 shadow-lg shadow-primary/10 hover:shadow-primary/20 transition-all text-sm">
        <i class="fa-solid fa-user-plus"></i> Új fiók
    </a>
</div>

<div class="bg-surface-container-lowest rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-surface-container-low">
                    <th class="px-8 py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Fiók</th>
                    <th class="px-6 py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Típus</th>
                    <th class="px-6 py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Bolt</th>
                    <th class="px-6 py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Státusz</th>
                    <th class="px-8 py-5 text-xs font-bold text-on-surface-variant uppercase tracking-widest text-right">Műveletek</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-container">
                <?php foreach ($data['users'] as $user): ?>
                <tr class="hover:bg-surface-container-low/50 transition-colors">
                    <td class="px-8 py-5">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-full <?= $user['role'] === 'tulajdonos' ? 'bg-tertiary-container' : 'bg-secondary-container' ?> flex items-center justify-center font-bold text-sm <?= $user['role'] === 'tulajdonos' ? 'text-on-tertiary-container' : 'text-on-secondary-container' ?>">
                                <?= e(mb_substr($user['name'], 0, 2)) ?>
                            </div>
                            <div>
                                <p class="font-bold text-on-surface"><?= e($user['name']) ?></p>
                                <p class="text-xs text-on-surface-variant"><?= e($user['email']) ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-5">
                        <?php if ($user['role'] === 'tulajdonos'): ?>
                            <span class="px-3 py-1 bg-tertiary-container text-on-tertiary-container text-[10px] font-bold uppercase rounded-full">Tulajdonos</span>
                        <?php else: ?>
                            <span class="px-3 py-1 bg-secondary-container text-on-secondary-container text-[10px] font-bold uppercase rounded-full">Bolt</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-5 text-sm font-medium text-on-surface-variant"><?= e($user['store_name'] ?? '—') ?></td>
                    <td class="px-6 py-5">
                        <?php if ($user['is_active']): ?>
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
                            <a href="<?= base_url("/users/{$user['id']}/edit") ?>" class="p-2 hover:bg-surface-container rounded-full transition-colors text-on-surface-variant">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <?php if ($user['id'] !== App\Core\Auth::id()): ?>
                            <form method="POST" action="<?= base_url("/users/{$user['id']}/delete") ?>" class="inline" onsubmit="return confirmDelete(this, '<?= e($user['name']) ?>')">
                                <?= csrf_field() ?>
                                <button type="submit" class="p-2 hover:bg-error-container/10 rounded-full transition-colors text-on-surface-variant hover:text-error">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
