<?php
$ownerUsers = $data['ownerUsers'] ?? [];
$storeUsers = $data['storeUsers'] ?? [];
$accountantUsers = $data['accountantUsers'] ?? [];
$allTabs = $data['allTabs'] ?? [];
$permissions = $data['permissions'] ?? [];
$allUsers = array_merge($ownerUsers, $storeUsers, $accountantUsers);
?>

<form method="POST" action="<?= base_url('/settings/permissions') ?>">
    <?= csrf_field() ?>

    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-6">
        <div>
            <h1 class="text-3xl font-heading font-extrabold text-on-surface tracking-tight mb-1">Tab jogosultságok</h1>
            <p class="text-on-surface-variant text-sm">Állítsd be melyik fiók mit láthat, rögzíthet és módosíthat.</p>
        </div>
        <button type="submit" class="px-6 py-3 bg-gradient-to-br from-primary to-primary-container text-on-primary-fixed font-bold rounded-full flex items-center gap-2 shadow-lg shadow-primary/10 hover:shadow-primary/20 transition-all text-sm">
            <i class="fa-solid fa-check"></i> Mentés
        </button>
    </div>

    <?php if (empty($allUsers)): ?>
        <div class="bg-surface-container-lowest rounded-xl p-8 text-center text-on-surface-variant">Nincs fiók a rendszerben.</div>
    <?php else: ?>
        <div class="bg-surface-container-lowest rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="bg-surface-container-low">
                            <th class="px-5 py-4 text-left text-xs font-bold text-on-surface-variant uppercase tracking-widest">Tab</th>
                            <?php foreach ($allUsers as $u): ?>
                            <th class="px-2 py-4 text-center text-xs font-bold text-on-surface-variant uppercase tracking-widest border-l border-surface-container" colspan="3">
                                <div class="flex flex-col items-center gap-0.5">
                                    <span><?= e($u['name']) ?></span>
                                    <span class="text-[9px] font-medium px-2 py-0.5 rounded-full <?php
                                        if ($u['role'] === 'tulajdonos') echo 'bg-amber-100 text-amber-700';
                                        elseif ($u['role'] === 'konyvelo') echo 'bg-blue-100 text-blue-600';
                                        else echo 'bg-surface-container text-on-surface-variant';
                                    ?>">
                                        <?= match($u['role']) { 'tulajdonos' => 'Tulajdonos', 'konyvelo' => 'Könyvelő', default => 'Bolt' } ?>
                                    </span>
                                </div>
                            </th>
                            <?php endforeach; ?>
                        </tr>
                        <tr class="bg-surface-container-low/50 border-b border-surface-container">
                            <th></th>
                            <?php foreach ($allUsers as $u): ?>
                            <th class="px-1 py-1 text-center text-[10px] text-gray-400 border-l border-surface-container">Lát</th>
                            <th class="px-1 py-1 text-center text-[10px] text-emerald-500">Rögz.</th>
                            <th class="px-1 py-1 text-center text-[10px] text-amber-500">Mód.</th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allTabs as $slug => $tab): ?>
                        <tr class="border-t border-surface-container hover:bg-surface-container-low/30 transition-colors">
                            <td class="px-5 py-3 font-medium flex items-center gap-2">
                                <i class="fa-solid <?= $tab['icon'] ?> text-sm text-on-surface-variant"></i>
                                <?= $tab['label'] ?>
                            </td>
                            <?php foreach ($allUsers as $u): ?>
                            <?php
                                $userPerms = array_column($permissions[$u['id']] ?? [], null, 'tab_slug');
                                $hasView   = ($userPerms[$slug]['can_view'] ?? 0) == 1;
                                $hasCreate = ($userPerms[$slug]['can_create'] ?? 0) == 1;
                                $hasEdit   = ($userPerms[$slug]['can_edit'] ?? 0) == 1;
                            ?>
                            <td class="px-1 py-3 text-center border-l border-surface-container">
                                <input type="checkbox"
                                       name="perms[<?= $u['id'] ?>][<?= $slug ?>][view]"
                                       value="1" <?= $hasView ? 'checked' : '' ?>
                                       class="h-4 w-4 text-primary border-outline-variant rounded focus:ring-primary-container">
                            </td>
                            <td class="px-1 py-3 text-center">
                                <input type="checkbox"
                                       name="perms[<?= $u['id'] ?>][<?= $slug ?>][create]"
                                       value="1" <?= $hasCreate ? 'checked' : '' ?>
                                       class="h-4 w-4 text-emerald-600 border-outline-variant rounded focus:ring-emerald-200">
                            </td>
                            <td class="px-1 py-3 text-center">
                                <input type="checkbox"
                                       name="perms[<?= $u['id'] ?>][<?= $slug ?>][edit]"
                                       value="1" <?= $hasEdit ? 'checked' : '' ?>
                                       class="h-4 w-4 text-amber-600 border-outline-variant rounded focus:ring-amber-200">
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</form>
