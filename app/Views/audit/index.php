<?php $logs = $data['logs'] ?? []; ?>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-heading font-bold text-gray-900">Módosítási napló</h3>
        <form method="GET" class="flex gap-2 items-center">
            <select name="table" class="px-3 py-1.5 border border-gray-300 rounded-xl text-sm focus:ring-primary/50" onchange="this.form.submit()">
                <option value="">Minden tábla</option>
                <?php foreach (['users','stores','employees','financial_records','salary_payments','evaluations','vacation_requests','schedules','defect_items','tab_permissions'] as $t): ?>
                    <option value="<?= $t ?>" <?= ($data['tableName'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Időpont</th>
                    <th>Felhasználó</th>
                    <th>Művelet</th>
                    <th>Tábla</th>
                    <th>Rekord ID</th>
                    <th>IP cím</th>
                    <th>Részletek</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="7" class="text-center text-gray-400 py-8">Nincs napló bejegyzés.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td class="whitespace-nowrap text-xs"><?= date('Y.m.d H:i:s', strtotime($log['created_at'])) ?></td>
                        <td class="text-sm"><?= e($log['user_name'] ?? 'Rendszer') ?></td>
                        <td>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold <?= match($log['action']) {
                                'create' => 'bg-green-100 text-green-700',
                                'update' => 'bg-blue-100 text-blue-700',
                                'delete' => 'bg-red-100 text-red-700',
                                default  => 'bg-gray-100 text-gray-600',
                            } ?>"><?= e($log['action']) ?></span>
                        </td>
                        <td class="text-xs text-gray-500"><?= e($log['table_name']) ?></td>
                        <td class="text-xs"><?= $log['record_id'] ?? '-' ?></td>
                        <td class="text-xs text-gray-400"><?= e($log['ip_address'] ?? '-') ?></td>
                        <td class="text-xs">
                            <?php if ($log['old_values'] || $log['new_values']): ?>
                                <button onclick="this.nextElementSibling.classList.toggle('hidden')" class="text-blue-600 hover:text-blue-800 text-xs">Mutasd</button>
                                <div class="hidden mt-2 p-2 bg-gray-50 rounded text-[10px] max-w-xs overflow-auto">
                                    <?php if ($log['old_values']): ?>
                                        <p class="font-bold text-red-500">Régi:</p>
                                        <pre class="whitespace-pre-wrap"><?= e($log['old_values']) ?></pre>
                                    <?php endif; ?>
                                    <?php if ($log['new_values']): ?>
                                        <p class="font-bold text-green-500 mt-1">Új:</p>
                                        <pre class="whitespace-pre-wrap"><?= e($log['new_values']) ?></pre>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($data['page'] > 1 || count($logs) >= 50): ?>
<div class="flex justify-between items-center mt-4">
    <?php if ($data['page'] > 1): ?>
        <a href="?page=<?= $data['page'] - 1 ?>&table=<?= e($data['tableName'] ?? '') ?>" class="text-sm text-blue-600 hover:text-blue-800">← Előző</a>
    <?php else: ?>
        <span></span>
    <?php endif; ?>
    <span class="text-sm text-gray-400">Oldal: <?= $data['page'] ?></span>
    <?php if (count($logs) >= 50): ?>
        <a href="?page=<?= $data['page'] + 1 ?>&table=<?= e($data['tableName'] ?? '') ?>" class="text-sm text-blue-600 hover:text-blue-800">Következő →</a>
    <?php endif; ?>
</div>
<?php endif; ?>
