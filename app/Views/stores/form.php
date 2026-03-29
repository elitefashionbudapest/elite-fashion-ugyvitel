<?php $store = $data['store'] ?? null; ?>
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

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="bg-sidebar text-primary px-6 py-3 rounded-full font-bold text-sm hover:bg-gray-800 transition-colors">
                    <?= $store ? 'Mentés' : 'Létrehozás' ?>
                </button>
                <a href="<?= base_url('/stores') ?>" class="text-gray-500 hover:text-gray-700 text-sm font-medium">Mégse</a>
            </div>
        </form>
    </div>
</div>
