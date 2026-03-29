<?php
$employee = $data['employee'] ?? null;
$stores = $data['stores'] ?? [];
$assignedStoreIds = $data['assignedStoreIds'] ?? [];
?>
<div class="max-w-lg">
    <div class="bg-white rounded-2xl shadow-sm p-6 sm:p-8">
        <h3 class="font-heading font-bold text-gray-900 mb-6"><?= $employee ? 'Dolgozó szerkesztése' : 'Új dolgozó' ?></h3>

        <form method="POST" action="<?= base_url($employee ? "/employees/{$employee['id']}" : '/employees') ?>" class="space-y-5">
            <?= csrf_field() ?>

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Dolgozó neve</label>
                <input type="text" id="name" name="name"
                       value="<?= e($employee ? $employee['name'] : old('name')) ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary/50 focus:border-primary text-sm"
                       required>
            </div>

            <!-- Bolt hozzárendelés -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Boltok</label>
                <div class="space-y-2">
                    <?php foreach ($stores as $store): ?>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="store_ids[]" value="<?= $store['id'] ?>"
                               <?= in_array($store['id'], $assignedStoreIds) ? 'checked' : '' ?>
                               class="h-4 w-4 text-sidebar border-gray-300 rounded focus:ring-primary">
                        <span class="text-sm text-gray-700"><?= e($store['name']) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Éves szabadság napok -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Éves szabadság (nap)</label>
                <input type="number" name="vacation_days_total" min="0" max="60"
                       value="<?= e($employee ? $employee['vacation_days_total'] : old('vacation_days_total', '20')) ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary/50 focus:border-primary text-sm"
                       placeholder="20">
            </div>

            <?php if ($employee): ?>
            <div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1"
                           <?= $employee['is_active'] ? 'checked' : '' ?>
                           class="h-4 w-4 text-sidebar border-gray-300 rounded focus:ring-primary">
                    <span class="text-sm text-gray-700">Aktív dolgozó</span>
                </label>
            </div>
            <?php endif; ?>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="bg-sidebar text-primary px-6 py-3 rounded-full font-bold text-sm hover:bg-gray-800 transition-colors">
                    <?= $employee ? 'Mentés' : 'Létrehozás' ?>
                </button>
                <a href="<?= base_url('/employees') ?>" class="text-gray-500 hover:text-gray-700 text-sm font-medium">Mégse</a>
            </div>
        </form>
    </div>
</div>
