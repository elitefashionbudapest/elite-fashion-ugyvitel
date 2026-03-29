<?php
use App\Core\Auth;

$employees = $data['employees'] ?? [];
?>
<div class="max-w-lg">
    <div class="bg-white rounded-2xl shadow-sm p-6 sm:p-8">
        <h3 class="font-heading font-bold text-gray-900 mb-6">Szabadság kérvény</h3>

        <!-- Figyelmeztetés: max 1 fő szabály -->
        <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-xl">
            <div class="flex items-start gap-2">
                <i class="fa-solid fa-triangle-exclamation text-yellow-600 text-base mt-0.5"></i>
                <div>
                    <p class="text-sm font-bold text-yellow-800">Fontos szabály!</p>
                    <p class="text-sm text-yellow-700 mt-1">Egyszerre az egész cégnél <strong>maximum 1 fő</strong> lehet szabadságon. A rendszer ellenőrzi, hogy nincs-e átfedés más jóváhagyott szabadsággal.</p>
                </div>
            </div>
        </div>

        <form method="POST" action="<?= base_url('/vacation') ?>" class="space-y-5">
            <?= csrf_field() ?>

            <!-- Dolgozó választás -->
            <div>
                <label for="employee_id" class="block text-sm font-medium text-gray-700 mb-1.5">Ki kéri a szabadságot?</label>
                <select id="employee_id" name="employee_id" class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-primary/50 focus:border-primary" required>
                    <option value="">-- Válasszon dolgozót --</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?= $emp['id'] ?>" <?= old('employee_id') == $emp['id'] ? 'selected' : '' ?>><?= e($emp['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Mettől -->
            <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1.5">Mettől</label>
                <input type="date" id="date_from" name="date_from"
                       value="<?= e(old('date_from')) ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-primary/50 focus:border-primary"
                       required>
            </div>

            <!-- Meddig -->
            <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1.5">Meddig</label>
                <input type="date" id="date_to" name="date_to"
                       value="<?= e(old('date_to')) ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-primary/50 focus:border-primary"
                       required>
            </div>

            <!-- Ellenőrizted checkbox -->
            <div class="p-4 bg-gray-50 rounded-xl">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="confirmed_no_overlap" value="1"
                           class="h-4 w-4 mt-0.5 text-sidebar border-gray-300 rounded focus:ring-primary"
                           required>
                    <span class="text-sm text-gray-700">
                        <strong>Ellenőriztem</strong> — megerősítem, hogy az adott időszakban nincs más jóváhagyott szabadság, és tisztában vagyok azzal, hogy egyszerre csak 1 fő lehet szabadságon.
                    </span>
                </label>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="bg-sidebar text-primary px-6 py-3 rounded-full font-bold text-sm hover:bg-gray-800 transition-colors">
                    Kérvény beadása
                </button>
                <a href="<?= base_url('/vacation') ?>" class="text-gray-500 hover:text-gray-700 text-sm font-medium">Mégse</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dateFrom = document.getElementById('date_from');
    const dateTo = document.getElementById('date_to');

    dateFrom.addEventListener('change', function() {
        if (dateTo.value && dateTo.value < dateFrom.value) {
            dateTo.value = dateFrom.value;
        }
        dateTo.min = dateFrom.value;
    });

    if (dateFrom.value) {
        dateTo.min = dateFrom.value;
    }
});
</script>
