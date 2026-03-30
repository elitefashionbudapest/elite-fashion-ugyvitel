<?php
$store = $data['store'] ?? null;
$openDays = $store ? explode(',', $store['open_days'] ?? '1,2,3,4,5,6') : ['1','2','3','4','5','6'];
$dayNames = ['1' => 'Hétfő', '2' => 'Kedd', '3' => 'Szerda', '4' => 'Csütörtök', '5' => 'Péntek', '6' => 'Szombat', '7' => 'Vasárnap'];
?>
<div class="max-w-lg">
    <div class="bg-white rounded-2xl shadow-sm p-6 sm:p-8">
        <h3 class="font-heading font-bold text-gray-900 mb-6"><?= $store ? 'Bolt szerkesztése' : 'Új bolt létrehozása' ?></h3>

        <form method="POST" action="<?= base_url($store ? "/stores/{$store['id']}" : '/stores') ?>" class="space-y-5">
            <?= csrf_field() ?>

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Bolt neve</label>
                <input type="text" id="name" name="name"
                       value="<?= e($store ? $store['name'] : old('name')) ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary/50 focus:border-primary text-sm"
                       placeholder="pl. Vörösmarty" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nyitvatartás</label>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($dayNames as $num => $name): ?>
                    <label class="flex items-center gap-2 cursor-pointer px-3 py-2 rounded-xl border border-gray-200 hover:border-primary/50 transition-all has-[:checked]:border-primary has-[:checked]:bg-primary/10">
                        <input type="checkbox" name="open_days[]" value="<?= $num ?>"
                               <?= in_array((string)$num, $openDays) ? 'checked' : '' ?>
                               class="h-4 w-4 text-primary border-gray-300 rounded focus:ring-primary/50">
                        <span class="text-sm font-medium"><?= $name ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="bg-sidebar text-primary px-6 py-3 rounded-full font-bold text-sm hover:bg-gray-800 transition-colors">
                    <?= $store ? 'Mentés' : 'Létrehozás' ?>
                </button>
                <a href="<?= base_url('/stores') ?>" class="text-gray-500 hover:text-gray-700 text-sm font-medium">Mégse</a>
            </div>
        </form>
    </div>
</div>
