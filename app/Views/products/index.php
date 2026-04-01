<?php
$products     = $data['products'] ?? [];
$productCount = $data['productCount'] ?? 0;
?>

<div class="flex flex-wrap flex-col md:flex-row md:items-end justify-between gap-6 mb-8">
    <div>
        <h1 class="text-3xl font-heading font-extrabold text-on-surface tracking-tight mb-1">Terméklista</h1>
        <p class="text-on-surface-variant text-sm">CSV fájlból importálható terméklista a vonalkód alapú azonosításhoz.</p>
    </div>
    <div class="flex items-center gap-3">
        <div class="px-4 py-2 bg-surface-container rounded-full text-sm font-bold text-on-surface">
            <i class="fa-solid fa-box mr-1"></i> <?= number_format($productCount, 0, ',', ' ') ?> termék
        </div>
    </div>
</div>

<!-- CSV Feltöltés -->
<div class="bg-surface-container-lowest rounded-lg p-6 mb-6">
    <h3 class="font-heading font-bold text-on-surface text-sm flex items-center gap-2 mb-4">
        <i class="fa-solid fa-file-csv text-primary"></i>
        CSV Importálás
    </h3>
    <p class="text-xs text-on-surface-variant mb-4">
        Tölts fel egy CSV fájlt pontosvesszővel (;) elválasztva.<br>
        Elvárt oszlopok: <strong>Terméknév; Termék kód; Vonalkód; Termék típus; Szabad készlet; Mennyiségi egység; Nettó eladási egységár; Áfa kulcs; Bruttó eladási egységár</strong>
    </p>

    <form method="POST" action="<?= base_url('/products/upload') ?>" enctype="multipart/form-data" class="flex flex-wrap items-end gap-4">
        <?= csrf_field() ?>
        <div class="flex-1 min-w-[250px]">
            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-2">CSV fájl</label>
            <input type="file" name="csv_file" accept=".csv"
                   class="w-full px-4 py-3 border border-outline-variant rounded-xl text-sm bg-surface-container-lowest file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary-container file:text-on-primary-container hover:file:bg-surface-variant cursor-pointer"
                   required>
        </div>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="replace_all" value="1" checked
                   class="w-4 h-4 rounded border-outline-variant text-primary focus:ring-primary-container">
            <span class="text-xs font-semibold text-on-surface-variant">Meglévő termékek cseréje</span>
        </label>
        <button type="submit" class="px-6 py-3 bg-primary text-on-primary font-semibold rounded-full flex items-center gap-2 hover:bg-primary/90 transition-colors text-sm">
            <i class="fa-solid fa-upload"></i> Importálás
        </button>
    </form>
</div>

<!-- Terméklista táblázat -->
<div class="bg-surface-container-lowest rounded-lg overflow-hidden">
    <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
        <table class="w-full text-left border-collapse">
            <thead class="sticky top-0 z-10">
                <tr class="bg-surface-container-low">
                    <th class="px-6 py-4 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Terméknév</th>
                    <th class="px-6 py-4 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Cikkszám</th>
                    <th class="px-6 py-4 text-xs font-bold text-on-surface-variant uppercase tracking-widest">Vonalkód</th>
                    <th class="px-6 py-4 text-xs font-bold text-on-surface-variant uppercase tracking-widest text-right">Nettó ár</th>
                    <th class="px-6 py-4 text-xs font-bold text-on-surface-variant uppercase tracking-widest text-right">Bruttó ár</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-container">
                <?php if (empty($products)): ?>
                    <tr><td colspan="5" class="px-8 py-12 text-center text-on-surface-variant">
                        <i class="fa-solid fa-box-open text-4xl mb-2 block text-outline-variant"></i>
                        Még nincs importált terméklista. Tölts fel egy CSV fájlt!
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                    <tr class="hover:bg-surface-container-low/50 transition-colors">
                        <td class="px-6 py-3 text-sm font-medium text-on-surface"><?= e($p['name']) ?></td>
                        <td class="px-6 py-3 font-mono text-xs text-on-surface-variant"><?= e($p['sku']) ?></td>
                        <td class="px-6 py-3 font-mono text-xs text-on-surface-variant"><?= e($p['barcode']) ?></td>
                        <td class="px-6 py-3 text-sm text-on-surface-variant text-right"><?= number_format((float)$p['net_price'], 0, ',', ' ') ?> Ft</td>
                        <td class="px-6 py-3 text-sm font-bold text-on-surface text-right"><?= number_format((float)$p['gross_price'], 0, ',', ' ') ?> Ft</td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
