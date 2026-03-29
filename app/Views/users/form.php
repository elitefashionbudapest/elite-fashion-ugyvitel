<?php
$user = $data['user'] ?? null;
$stores = $data['stores'] ?? [];
?>
<div class="max-w-lg">
    <div class="bg-white rounded-2xl shadow-sm p-6 sm:p-8">
        <h3 class="font-heading font-bold text-gray-900 mb-6"><?= $user ? 'Fiók szerkesztése' : 'Új fiók létrehozása' ?></h3>

        <form method="POST" action="<?= base_url($user ? "/users/{$user['id']}" : '/users') ?>" class="space-y-5">
            <?= csrf_field() ?>

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Név</label>
                <input type="text" id="name" name="name"
                       value="<?= e($user ? $user['name'] : old('name')) ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary/50 focus:border-primary text-sm" required>
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                <input type="email" id="email" name="email"
                       value="<?= e($user ? $user['email'] : old('email')) ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary/50 focus:border-primary text-sm" required>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">
                    Jelszó <?= $user ? '<span class="text-gray-400 font-normal">(hagyja üresen ha nem változtat)</span>' : '' ?>
                </label>
                <input type="password" id="password" name="password"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary/50 focus:border-primary text-sm"
                       <?= $user ? '' : 'required' ?>>
            </div>

            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 mb-1.5">Típus</label>
                <select id="role" name="role" onchange="toggleStoreField()"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary/50 focus:border-primary text-sm">
                    <option value="tulajdonos" <?= ($user && $user['role'] === 'tulajdonos') || old('role') === 'tulajdonos' ? 'selected' : '' ?>>Tulajdonos</option>
                    <option value="bolt" <?= ($user && $user['role'] === 'bolt') || (!$user && old('role') !== 'tulajdonos') ? 'selected' : '' ?>>Bolt fiók</option>
                </select>
            </div>

            <div id="store-field" class="<?= ($user && $user['role'] === 'tulajdonos') ? 'hidden' : '' ?>">
                <label for="store_id" class="block text-sm font-medium text-gray-700 mb-1.5">Bolt</label>
                <select id="store_id" name="store_id"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary/50 focus:border-primary text-sm">
                    <option value="">-- Válasszon --</option>
                    <?php foreach ($stores as $store): ?>
                        <option value="<?= $store['id'] ?>" <?= ($user && $user['store_id'] == $store['id']) ? 'selected' : '' ?>><?= e($store['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($user): ?>
            <div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1"
                           <?= $user['is_active'] ? 'checked' : '' ?>
                           class="h-4 w-4 text-sidebar border-gray-300 rounded focus:ring-primary">
                    <span class="text-sm text-gray-700">Aktív fiók</span>
                </label>
            </div>
            <?php endif; ?>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="bg-sidebar text-primary px-6 py-3 rounded-full font-bold text-sm hover:bg-gray-800 transition-colors">
                    <?= $user ? 'Mentés' : 'Létrehozás' ?>
                </button>
                <a href="<?= base_url('/users') ?>" class="text-gray-500 hover:text-gray-700 text-sm font-medium">Mégse</a>
            </div>
        </form>
    </div>
</div>

<script>
function toggleStoreField() {
    const role = document.getElementById('role').value;
    const storeField = document.getElementById('store-field');
    storeField.classList.toggle('hidden', role === 'tulajdonos');
}
</script>
