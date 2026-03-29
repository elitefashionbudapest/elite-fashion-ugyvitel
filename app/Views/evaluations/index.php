<?php
use App\Core\Auth;

$evaluations     = $data['evaluations'] ?? [];
$stores          = $data['stores'] ?? [];
$premiumStatuses = $data['premiumStatuses'] ?? [];
$filters         = $data['filters'] ?? [];
?>

<!-- Szurok -->
<div class="bg-white rounded-2xl shadow-sm p-4 sm:p-6 mb-6">
    <form method="GET" action="<?= base_url('/evaluations') ?>" class="flex flex-wrap gap-3 items-end">
        <?php if (Auth::isOwner()): ?>
        <div class="flex-1 min-w-[150px]">
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Bolt</label>
            <select name="store_id" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm focus:ring-primary/50 focus:border-primary">
                <option value="">Mind</option>
                <?php foreach ($stores as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= ($filters['store_id'] ?? '') == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Datumtol</label>
            <input type="date" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>" class="px-3 py-2 border border-gray-300 rounded-xl text-sm focus:ring-primary/50 focus:border-primary">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Datumig</label>
            <input type="date" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>" class="px-3 py-2 border border-gray-300 rounded-xl text-sm focus:ring-primary/50 focus:border-primary">
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Honap</label>
            <select name="month" class="px-3 py-2 border border-gray-300 rounded-xl text-sm focus:ring-primary/50 focus:border-primary">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= ($filters['month'] ?? date('n')) == $m ? 'selected' : '' ?>><?= str_pad($m, 2, '0', STR_PAD_LEFT) ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Ev</label>
            <select name="year" class="px-3 py-2 border border-gray-300 rounded-xl text-sm focus:ring-primary/50 focus:border-primary">
                <?php for ($y = date('Y'); $y >= date('Y') - 2; $y--): ?>
                    <option value="<?= $y ?>" <?= ($filters['year'] ?? date('Y')) == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-sidebar text-primary rounded-xl text-sm font-bold hover:bg-gray-800 transition-colors">Szures</button>
        <a href="<?= base_url('/evaluations') ?>" class="px-4 py-2 text-gray-500 hover:text-gray-700 text-sm">Torles</a>
    </form>
</div>

<!-- Premium osszesito -->
<?php if (!empty($premiumStatuses)): ?>
<div class="bg-white rounded-2xl shadow-sm p-4 sm:p-6 mb-6">
    <h3 class="font-heading font-bold text-gray-900 mb-4">Premium statusz - <?= str_pad($filters['month'] ?? date('n'), 2, '0', STR_PAD_LEFT) ?>/<?= $filters['year'] ?? date('Y') ?></h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <?php foreach ($premiumStatuses as $ps): ?>
            <?php
                $pct = round($ps['ratio'] * 100, 1);
                if ($pct >= 90) {
                    $badgeClass = 'bg-green-100 text-green-700';
                    $label = 'PREMIUM';
                } elseif ($pct >= 70) {
                    $badgeClass = 'bg-yellow-100 text-yellow-700';
                    $label = 'Kozel';
                } else {
                    $badgeClass = 'bg-red-100 text-red-700';
                    $label = 'Alacsony';
                }
            ?>
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                <div>
                    <span class="font-medium text-sm text-gray-900"><?= e($ps['employee_name']) ?></span>
                    <span class="text-xs text-gray-500 ml-2"><?= $ps['totalReviews'] ?>/<?= $ps['totalCustomers'] ?> = <?= $pct ?>%</span>
                </div>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold <?= $badgeClass ?>"><?= $label ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Ertekelesek tabla -->
<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <div class="px-6 py-4 flex flex-wrap items-center justify-between border-b border-gray-100">
        <h3 class="font-heading font-bold text-gray-900">Ertekelesek</h3>
        <a href="<?= base_url('/evaluations/create') ?>" class="bg-sidebar text-primary px-4 py-2 rounded-full text-sm font-bold hover:bg-gray-800 transition-colors flex items-center gap-1">
            <i class="fa-solid fa-plus text-base"></i> Uj ertekeles
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Bolt</th>
                    <th class="text-right">Vasarlok</th>
                    <th class="text-right">Ertekelesek</th>
                    <th>Dolgozok</th>
                    <th class="text-right">Arany</th>
                    <th>Statusz</th>
                    <th class="text-right">Muveletek</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($evaluations)): ?>
                    <tr><td colspan="8" class="text-center text-gray-400 py-8">Nincs talalat.</td></tr>
                <?php else: ?>
                    <?php foreach ($evaluations as $ev): ?>
                    <?php
                        $ratio = $ev['customer_count'] > 0
                            ? $ev['google_review_count'] / $ev['customer_count']
                            : 0;
                        $pct = round($ratio * 100, 1);
                        if ($pct >= 90) {
                            $badgeClass = 'bg-green-100 text-green-700';
                            $statusLabel = 'Kivalao';
                        } elseif ($pct >= 70) {
                            $badgeClass = 'bg-yellow-100 text-yellow-700';
                            $statusLabel = 'Jo';
                        } else {
                            $badgeClass = 'bg-red-100 text-red-700';
                            $statusLabel = 'Alacsony';
                        }
                    ?>
                    <tr>
                        <td class="whitespace-nowrap"><?= date('Y.m.d', strtotime($ev['record_date'])) ?></td>
                        <td><?= e($ev['store_name'] ?? '-') ?></td>
                        <td class="text-right font-medium"><?= format_number($ev['customer_count']) ?></td>
                        <td class="text-right font-medium"><?= format_number($ev['google_review_count']) ?></td>
                        <td class="text-gray-500 text-sm max-w-[200px] truncate"><?= e($ev['worker_names'] ?? '-') ?></td>
                        <td class="text-right font-medium"><?= $pct ?>%</td>
                        <td>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold <?= $badgeClass ?>"><?= $statusLabel ?></span>
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <form method="POST" action="<?= base_url("/evaluations/{$ev['id']}/delete") ?>" class="inline" onsubmit="return confirm('Biztosan torli az ertekelest?')">
                                <?= csrf_field() ?>
                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">Torles</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
