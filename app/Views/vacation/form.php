<?php
use App\Core\Auth;

$employees = $data['employees'] ?? [];
$type = old('type', 'szabadsag');
?>
<div class="max-w-lg">
    <div class="bg-white rounded-2xl shadow-sm p-6 sm:p-8">
        <h3 class="font-heading font-bold text-gray-900 mb-6">Szabadság / Szabadnap kérvény</h3>

        <!-- Típus választó -->
        <div class="flex gap-1 bg-gray-100 p-1 rounded-lg w-fit mb-6">
            <label class="cursor-pointer">
                <input type="radio" name="type_toggle" value="szabadsag" class="hidden peer" <?= $type === 'szabadsag' ? 'checked' : '' ?> onchange="toggleVacationType('szabadsag')">
                <span class="peer-checked:bg-white peer-checked:shadow-sm px-4 py-2 rounded-lg text-sm font-semibold transition-all text-gray-500 peer-checked:text-gray-900 block">
                    <i class="fa-solid fa-umbrella-beach mr-1"></i>Szabadság
                </span>
            </label>
            <label class="cursor-pointer">
                <input type="radio" name="type_toggle" value="szabadnap" class="hidden peer" <?= $type === 'szabadnap' ? 'checked' : '' ?> onchange="toggleVacationType('szabadnap')">
                <span class="peer-checked:bg-white peer-checked:shadow-sm px-4 py-2 rounded-lg text-sm font-semibold transition-all text-gray-500 peer-checked:text-gray-900 block">
                    <i class="fa-solid fa-calendar-xmark mr-1"></i>Kivételes szabadnap
                </span>
            </label>
        </div>

        <!-- Szabadság figyelmeztetés -->
        <div id="szabadsag-info" class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-xl <?= $type === 'szabadnap' ? 'hidden' : '' ?>">
            <div class="flex items-start gap-2">
                <i class="fa-solid fa-triangle-exclamation text-yellow-600 text-base mt-0.5"></i>
                <div>
                    <p class="text-sm font-bold text-yellow-800">Fontos szabály!</p>
                    <p class="text-sm text-yellow-700 mt-1">Egyszerre az egész cégnél <strong>maximum 1 fő</strong> lehet szabadságon. Beleszámít a szabadságkeretbe.</p>
                </div>
            </div>
        </div>

        <!-- Szabadnap info -->
        <div id="szabadnap-info" class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-xl <?= $type === 'szabadsag' ? 'hidden' : '' ?>">
            <div class="flex items-start gap-2">
                <i class="fa-solid fa-circle-info text-blue-600 text-base mt-0.5"></i>
                <div>
                    <p class="text-sm font-bold text-blue-800">Kivételes szabadnap</p>
                    <p class="text-sm text-blue-700 mt-1">Nem számít bele a szabadságkeretbe. Pl. ünnepnapra eső szabadnap, vagy nem a megszokott napon kivett pihenőnap.</p>
                </div>
            </div>
        </div>

        <form method="POST" action="<?= base_url('/vacation') ?>" class="space-y-5">
            <?= csrf_field() ?>
            <input type="hidden" name="type" id="vacation-type" value="<?= e($type) ?>">

            <div>
                <label for="employee_id" class="block text-sm font-medium text-gray-700 mb-1.5">Ki kéri?</label>
                <select id="employee_id" name="employee_id" class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-primary/50 focus:border-primary" required>
                    <option value="">-- Válasszon dolgozót --</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?= $emp['id'] ?>" <?= old('employee_id') == $emp['id'] ? 'selected' : '' ?>><?= e($emp['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1.5">Mettől</label>
                <input type="date" id="date_from" name="date_from" value="<?= e(old('date_from')) ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-primary/50 focus:border-primary" required>
            </div>

            <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1.5">Meddig</label>
                <input type="date" id="date_to" name="date_to" value="<?= e(old('date_to')) ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-primary/50 focus:border-primary" required>
            </div>

            <!-- Ellenőrzés checkbox (csak szabadságnál) -->
            <div id="overlap-check" class="p-4 bg-gray-50 rounded-xl <?= $type === 'szabadnap' ? 'hidden' : '' ?>">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="confirmed_no_overlap" value="1" id="confirm-checkbox"
                           class="h-4 w-4 mt-0.5 text-sidebar border-gray-300 rounded focus:ring-primary"
                           <?= $type === 'szabadsag' ? 'required' : '' ?>>
                    <span class="text-sm text-gray-700">
                        <strong>Ellenőriztem</strong> — nincs átfedés más jóváhagyott szabadsággal.
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
function toggleVacationType(type) {
    document.getElementById('vacation-type').value = type;
    document.getElementById('szabadsag-info').classList.toggle('hidden', type !== 'szabadsag');
    document.getElementById('szabadnap-info').classList.toggle('hidden', type !== 'szabadnap');
    const overlapCheck = document.getElementById('overlap-check');
    const checkbox = document.getElementById('confirm-checkbox');
    overlapCheck.classList.toggle('hidden', type !== 'szabadsag');
    checkbox.required = (type === 'szabadsag');
    if (type === 'szabadnap') checkbox.checked = false;
}

document.addEventListener('DOMContentLoaded', function() {
    const dateFrom = document.getElementById('date_from');
    const dateTo = document.getElementById('date_to');
    dateFrom.addEventListener('change', function() {
        if (dateTo.value && dateTo.value < dateFrom.value) dateTo.value = dateFrom.value;
        dateTo.min = dateFrom.value;
    });
    if (dateFrom.value) dateTo.min = dateFrom.value;
});
</script>
