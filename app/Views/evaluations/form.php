<?php
use App\Core\Auth;

$stores    = $data['stores'] ?? [];
$employees = $data['employees'] ?? [];
$storeId   = $data['storeId'] ?? null;
?>
<div class="max-w-xl">
    <div class="bg-white rounded-2xl shadow-sm p-6 sm:p-8">
        <h3 class="font-heading font-bold text-gray-900 mb-6">Uj ertekeles rogzitese</h3>

        <form method="POST" action="<?= base_url('/evaluations') ?>" class="space-y-5">
            <?= csrf_field() ?>

            <!-- Uzlet -->
            <?php if (Auth::isOwner()): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Uzlet</label>
                <select name="store_id" id="store-select" class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-primary/50 focus:border-primary" required>
                    <option value="">-- Valasszon --</option>
                    <?php foreach ($stores as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= old('store_id') == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <!-- Datum -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Datum</label>
                <input type="date" name="record_date"
                       value="<?= e(old('record_date', date('Y-m-d'))) ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-primary/50 focus:border-primary" required>
            </div>

            <!-- Vasarlok szama -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Vasarlok szama</label>
                <input type="number" name="customer_count" min="0" step="1"
                       value="<?= e(old('customer_count', '')) ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-primary/50 focus:border-primary" required>
                <p class="text-xs text-gray-400 mt-1">15 000 Ft felett ajandek gyertya</p>
            </div>

            <!-- Ertekelesek szama -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Google ertekelesek szama</label>
                <input type="number" name="google_review_count" min="0" step="1"
                       value="<?= e(old('google_review_count', '')) ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl text-sm focus:ring-primary/50 focus:border-primary" required>
            </div>

            <!-- Ki dolgozott aznap -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Ki dolgozott aznap?</label>
                <div id="workers-container" class="space-y-2">
                    <?php if (!empty($employees)): ?>
                        <?php foreach ($employees as $emp): ?>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="worker_ids[]" value="<?= $emp['id'] ?>"
                                   class="h-4 w-4 text-sidebar border-gray-300 rounded focus:ring-primary">
                            <span class="text-sm text-gray-700"><?= e($emp['name']) ?></span>
                        </label>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-sm text-gray-400" id="workers-placeholder">
                            <?= Auth::isOwner() ? 'Valasszon boltat a dolgozok betoltesehez.' : 'Nincsenek dolgozok ehhez a bolthoz.' ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="bg-sidebar text-primary px-6 py-3 rounded-full font-bold text-sm hover:bg-gray-800 transition-colors">
                    Rogzites
                </button>
                <a href="<?= base_url('/evaluations') ?>" class="text-gray-500 hover:text-gray-700 text-sm font-medium">Megse</a>
            </div>
        </form>
    </div>
</div>

<?php if (Auth::isOwner()): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const storeSelect = document.getElementById('store-select');
    const container = document.getElementById('workers-container');

    if (!storeSelect) return;

    storeSelect.addEventListener('change', function() {
        const storeId = this.value;
        container.innerHTML = '<p class="text-sm text-gray-400">Betoltes...</p>';

        if (!storeId) {
            container.innerHTML = '<p class="text-sm text-gray-400">Valasszon boltat a dolgozok betoltesehez.</p>';
            return;
        }

        fetch(baseUrl + '/evaluations/employees/' + storeId)
            .then(function(response) { return response.json(); })
            .then(function(employees) {
                if (employees.length === 0) {
                    container.innerHTML = '<p class="text-sm text-gray-400">Nincsenek dolgozok ehhez a bolthoz.</p>';
                    return;
                }

                var html = '';
                employees.forEach(function(emp) {
                    html += '<label class="flex items-center gap-2 cursor-pointer">' +
                        '<input type="checkbox" name="worker_ids[]" value="' + emp.id + '" ' +
                        'class="h-4 w-4 text-sidebar border-gray-300 rounded focus:ring-primary">' +
                        '<span class="text-sm text-gray-700">' + escapeHtml(emp.name) + '</span>' +
                        '</label>';
                });
                container.innerHTML = html;
            })
            .catch(function() {
                container.innerHTML = '<p class="text-sm text-red-400">Hiba a dolgozok betoltesekor.</p>';
            });
    });

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }
});

var baseUrl = <?= json_encode(rtrim(base_url(''), '/')) ?>;
</script>
<?php endif; ?>
