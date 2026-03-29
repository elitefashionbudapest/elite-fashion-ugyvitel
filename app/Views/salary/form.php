<?php
use App\Models\{SalaryPayment, OwnerPayment};

$employees = $data['employees'] ?? [];
$type = $data['type'] ?? 'dolgozoi';
$inputCls = 'w-full px-4 py-3 border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary-container focus:border-primary bg-surface-container-lowest';

$months = [
    1 => 'Január', 2 => 'Február', 3 => 'Március',
    4 => 'Április', 5 => 'Május', 6 => 'Június',
    7 => 'Július', 8 => 'Augusztus', 9 => 'Szeptember',
    10 => 'Október', 11 => 'November', 12 => 'December',
];
?>
<div class="max-w-3xl">
    <div class="bg-surface-container-lowest rounded-xl p-6 sm:p-8">
        <h3 class="font-heading font-bold text-on-surface text-xl mb-4">
            <?= $type === 'tulajdonosi' ? 'Tulajdonosi fizetés rögzítés' : 'Dolgozói fizetés rögzítés' ?>
        </h3>

        <!-- Típus váltó -->
        <?php if (Auth::isOwner()): ?>
        <div class="flex gap-1 bg-surface-container-low p-1 rounded-lg w-fit mb-6">
            <a href="<?= base_url('/salary/create?type=dolgozoi') ?>"
               class="px-4 py-2 rounded-lg text-xs font-semibold transition-all <?= $type === 'dolgozoi' ? 'bg-surface-container-lowest text-on-surface shadow-sm' : 'text-on-surface-variant hover:text-on-surface' ?>">
                Dolgozói
            </a>
            <a href="<?= base_url('/salary/create?type=tulajdonosi') ?>"
               class="px-4 py-2 rounded-lg text-xs font-semibold transition-all <?= $type === 'tulajdonosi' ? 'bg-surface-container-lowest text-on-surface shadow-sm' : 'text-on-surface-variant hover:text-on-surface' ?>">
                Tulajdonosi
            </a>
        </div>
        <?php endif; ?>

        <form method="POST" action="<?= base_url('/salary') ?>" class="space-y-5">
            <?= csrf_field() ?>
            <input type="hidden" name="type" value="<?= e($type) ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- BAL -->
                <div class="space-y-5">
                    <?php if ($type === 'tulajdonosi'): ?>
                    <div>
                        <label class="block text-sm font-medium text-on-surface mb-2">Tulajdonos</label>
                        <div class="flex gap-3">
                            <?php foreach (OwnerPayment::OWNERS as $key => $label): ?>
                            <label class="flex-1 flex items-center justify-center gap-2 cursor-pointer p-3 rounded-lg hover:bg-surface-container-low transition-colors border border-surface-container text-center">
                                <input type="radio" name="owner_name" value="<?= e($key) ?>" <?= old('owner_name') === $key ? 'checked' : '' ?>
                                       class="h-4 w-4 text-primary border-outline focus:ring-primary-container" required>
                                <span class="text-sm font-medium text-on-surface"><?= e($label) ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <div>
                        <label class="block text-sm font-medium text-on-surface mb-1.5">Dolgozó</label>
                        <select name="employee_id" class="<?= $inputCls ?>" required>
                            <option value="">— Válasszon dolgozót —</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= $emp['id'] ?>" <?= old('employee_id') == $emp['id'] ? 'selected' : '' ?>><?= e($emp['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-on-surface mb-1.5">Év</label>
                            <select name="year" class="<?= $inputCls ?>" required>
                                <?php for ($y = 2030; $y >= 2017; $y--): ?>
                                    <option value="<?= $y ?>" <?= (old('year', (string)date('Y'))) == $y ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-on-surface mb-1.5">Hónap</label>
                            <select name="month" class="<?= $inputCls ?>" required>
                                <option value="">— Hónap —</option>
                                <?php foreach ($months as $num => $name): ?>
                                    <option value="<?= $num ?>" <?= (old('month', (string)date('n'))) == $num ? 'selected' : '' ?>><?= e($name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- JOBB -->
                <div class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-on-surface mb-2">Honnan fizetve</label>
                        <div class="grid grid-cols-2 gap-2">
                            <?php $sources = $type === 'tulajdonosi' ? OwnerPayment::SOURCES : SalaryPayment::SOURCES; ?>
                            <?php foreach ($sources as $key => $label): ?>
                            <label class="flex items-center gap-2 cursor-pointer p-3 rounded-lg hover:bg-surface-container-low transition-colors border border-surface-container">
                                <input type="radio" name="source" value="<?= $key ?>" <?= old('source') === $key ? 'checked' : '' ?>
                                       class="h-4 w-4 text-primary border-outline focus:ring-primary-container" required>
                                <span class="text-sm font-medium text-on-surface"><?= e($label) ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-on-surface mb-1.5">Összeg</label>
                        <input type="number" name="amount" step="1" min="1" value="<?= e(old('amount')) ?>"
                               class="<?= $inputCls ?>" placeholder="0" required>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2 border-t border-surface-container">
                <button type="submit" class="px-6 py-3 bg-gradient-to-br from-primary to-primary-container text-on-primary-fixed font-bold rounded-full text-sm shadow-lg shadow-primary/10 hover:shadow-primary/20 transition-all">
                    Rögzítés
                </button>
                <a href="<?= base_url('/salary?tab=' . $type) ?>" class="text-on-surface-variant hover:text-on-surface text-sm font-medium">Mégse</a>
            </div>
        </form>
    </div>
</div>
