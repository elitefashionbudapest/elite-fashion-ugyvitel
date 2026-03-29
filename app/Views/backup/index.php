<?php $backups = $data['backups'] ?? []; ?>

<div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8">
    <div>
        <h1 class="text-3xl font-heading font-extrabold text-on-surface tracking-tight mb-1">Adatmentés</h1>
        <p class="text-on-surface-variant text-sm">Adatbázis mentés készítése és visszaállítás. Frissítés előtt mindig készíts mentést!</p>
    </div>
    <form method="POST" action="<?= base_url('/backup/create') ?>">
        <?= csrf_field() ?>
        <button type="submit" class="px-6 py-3 bg-gradient-to-br from-primary to-primary-container text-on-primary-fixed font-bold rounded-full flex items-center gap-2 shadow-lg shadow-primary/10 hover:shadow-primary/20 transition-all text-sm">
            <i class="fa-solid fa-download"></i> Új mentés készítése
        </button>
    </form>
</div>

<!-- Visszaállítás info -->
<div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6 flex items-start gap-3">
    <i class="fa-solid fa-circle-info text-amber-600 mt-0.5"></i>
    <div class="text-sm text-amber-800">
        <p class="font-bold mb-1">Visszaállítás módja:</p>
        <ol class="list-decimal ml-4 space-y-0.5 text-amber-700">
            <li>Töltsd le a mentés .zip fájlt</li>
            <li>Csomagold ki — benne van a <strong>database.sql</strong> (adatbázis) és az <strong>uploads/</strong> mappa (képek)</li>
            <li>Az adatbázishoz: phpMyAdmin → Importálás → database.sql feltöltése</li>
            <li>A képekhez: az uploads/ mappát másold fel a szerver public/ mappájába</li>
        </ol>
    </div>
</div>

<!-- Mentések listája -->
<div class="bg-surface-container-lowest rounded-xl overflow-hidden">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-surface-container-low">
                <th class="px-8 py-4 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Fájlnév</th>
                <th class="px-6 py-4 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Dátum</th>
                <th class="px-6 py-4 text-xs font-bold text-on-surface-variant uppercase tracking-widest text-right">Méret</th>
                <th class="px-8 py-4 text-xs font-bold text-on-surface-variant uppercase tracking-widest text-right">Műveletek</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-surface-container">
            <?php if (empty($backups)): ?>
                <tr><td colspan="4" class="px-8 py-12 text-center text-on-surface-variant">
                    <i class="fa-solid fa-database text-4xl mb-2 block text-outline-variant"></i>
                    Nincs mentés. Készíts egyet a gombbal!
                </td></tr>
            <?php else: ?>
                <?php foreach ($backups as $b): ?>
                <tr class="hover:bg-surface-container-low/50 transition-colors">
                    <td class="px-8 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-emerald-100 flex items-center justify-center">
                                <i class="fa-solid fa-database text-emerald-600 text-sm"></i>
                            </div>
                            <span class="font-mono text-sm font-medium text-on-surface"><?= e($b['filename']) ?></span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-on-surface-variant"><?= e($b['date']) ?></td>
                    <td class="px-6 py-4 text-sm text-right text-on-surface-variant">
                        <?php
                            $size = $b['size'];
                            if ($size >= 1048576) echo round($size / 1048576, 1) . ' MB';
                            elseif ($size >= 1024) echo round($size / 1024, 1) . ' KB';
                            else echo $size . ' B';
                        ?>
                    </td>
                    <td class="px-8 py-4 text-right">
                        <a href="<?= base_url('/backup/download/' . urlencode($b['filename'])) ?>" class="p-2 hover:bg-surface-container rounded-full transition-colors text-primary inline-block" title="Letöltés">
                            <i class="fa-solid fa-download"></i>
                        </a>
                        <form method="POST" action="<?= base_url('/backup/delete/' . urlencode($b['filename'])) ?>" class="inline" onsubmit="return confirm('Biztosan törli a mentést?')">
                            <?= csrf_field() ?>
                            <button type="submit" class="p-2 hover:bg-error-container/10 rounded-full transition-colors text-on-surface-variant hover:text-error" title="Törlés">
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
