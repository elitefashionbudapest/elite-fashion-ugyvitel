<?php
use App\Core\Auth;
$employees = $data['employees'] ?? [];
?>
<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <div class="px-6 py-4 flex flex-wrap items-center justify-between gap-3 border-b border-gray-100">
        <h3 class="font-heading font-bold text-gray-900">Szabadság kérvényező</h3>
        <a href="<?= base_url('/vacation/create') ?>" class="bg-sidebar text-primary px-4 py-2 rounded-full text-sm font-bold hover:bg-gray-800 transition-colors flex items-center gap-1">
            <i class="fa-solid fa-plus text-base"></i> Új kérvény
        </a>
    </div>

    <!-- Szűrők -->
    <div class="px-6 py-3 border-b border-gray-100 bg-gray-50/50">
        <form method="GET" action="<?= base_url('/vacation') ?>" class="flex flex-wrap items-center gap-3">
            <label class="text-sm text-gray-600 font-medium">Dolgozó:</label>
            <select name="employee_id" onchange="this.form.submit()" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-primary/50 focus:border-primary">
                <option value="">Mindenki</option>
                <?php foreach ($employees as $emp): ?>
                    <option value="<?= $emp['id'] ?>" <?= ($data['filters']['employee_id'] ?? '') == $emp['id'] ? 'selected' : '' ?>><?= e($emp['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <label class="text-sm text-gray-600 font-medium ml-2">Státusz:</label>
            <select name="status" onchange="this.form.submit()" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-primary/50 focus:border-primary">
                <option value="">Mind</option>
                <option value="pending" <?= ($data['filters']['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Függőben</option>
                <option value="approved" <?= ($data['filters']['status'] ?? '') === 'approved' ? 'selected' : '' ?>>Jóváhagyva</option>
                <option value="rejected" <?= ($data['filters']['status'] ?? '') === 'rejected' ? 'selected' : '' ?>>Elutasítva</option>
            </select>

            <label class="text-sm text-gray-600 font-medium ml-2">Mettől:</label>
            <input type="date" name="date_from" value="<?= e($data['filters']['date_from'] ?? '') ?>" onchange="this.form.submit()" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-primary/50 focus:border-primary">

            <label class="text-sm text-gray-600 font-medium">Meddig:</label>
            <input type="date" name="date_to" value="<?= e($data['filters']['date_to'] ?? '') ?>" onchange="this.form.submit()" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-primary/50 focus:border-primary">

            <?php if (!empty($data['filters']['employee_id']) || !empty($data['filters']['status']) || !empty($data['filters']['date_from']) || !empty($data['filters']['date_to'])): ?>
                <a href="<?= base_url('/vacation') ?>" class="text-xs text-gray-500 hover:text-gray-700 font-medium"><i class="fa-solid fa-xmark"></i> Szűrők törlése</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="overflow-x-auto">
    <table class="data-table">
        <thead>
            <tr>
                <th>Dolgozó</th>
                <th>Mettől</th>
                <th>Meddig</th>
                <th>Státusz</th>
                <th class="hide-mobile">Elbírálta</th>
                <th class="text-right">Műveletek</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data['requests'])): ?>
                <tr><td colspan="6" class="text-center text-gray-400 py-8">Nincs szabadság kérvény.</td></tr>
            <?php else: ?>
                <?php foreach ($data['requests'] as $req): ?>
                <tr>
                    <td class="font-medium"><?= e($req['employee_name']) ?></td>
                    <td class="text-sm text-gray-600"><?= e($req['date_from']) ?></td>
                    <td class="text-sm text-gray-600"><?= e($req['date_to']) ?></td>
                    <td>
                        <?php if ($req['status'] === 'pending'): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-yellow-100 text-yellow-700">Függőben</span>
                        <?php elseif ($req['status'] === 'approved'): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-700">Jóváhagyva</span>
                        <?php elseif ($req['status'] === 'rejected'): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">Elutasítva</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-sm text-gray-500 hide-mobile"><?= e($req['approved_by_name'] ?? '-') ?></td>
                    <td class="text-right">
                        <?php if (Auth::isOwner() && $req['status'] === 'pending'): ?>
                            <form method="POST" action="<?= base_url("/vacation/{$req['id']}/approve") ?>" class="inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="text-green-600 hover:text-green-800 text-sm font-medium mr-2"><i class="fa-solid fa-check"></i></button>
                            </form>
                            <form method="POST" action="<?= base_url("/vacation/{$req['id']}/reject") ?>" class="inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="text-orange-600 hover:text-orange-800 text-sm font-medium mr-2"><i class="fa-solid fa-xmark"></i></button>
                            </form>
                        <?php endif; ?>
                        <form method="POST" action="<?= base_url("/vacation/{$req['id']}/delete") ?>" class="inline" onsubmit="return confirmDelete(this, '<?= e($req['employee_name']) ?> szabadsága')">
                            <?= csrf_field() ?>
                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
