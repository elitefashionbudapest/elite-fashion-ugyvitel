<?php
$banks = $data['banks'] ?? [];
$inputCls = 'w-full px-4 py-3 border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary-container focus:border-primary bg-surface-container-lowest';
?>

<div class="max-w-lg">
    <div class="bg-surface-container-lowest rounded-xl p-6 sm:p-8">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-cyan-100 flex items-center justify-center">
                <i class="fa-solid fa-file-import text-cyan-600"></i>
            </div>
            <div>
                <h3 class="font-heading font-bold text-on-surface text-xl">Banki kivonat importálás</h3>
                <p class="text-xs text-on-surface-variant">OTP vagy CIB CSV kivonat feltöltése és feldolgozása.</p>
            </div>
        </div>

        <form method="POST" action="<?= base_url('/bank-transactions/import/upload') ?>" enctype="multipart/form-data" class="space-y-5">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Melyik bankszámlához?</label>
                <select name="bank_id" class="<?= $inputCls ?>" required>
                    <option value="">-- Válasszon bankot --</option>
                    <?php foreach ($banks as $b): ?>
                        <option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">CSV fájl</label>
                <input type="file" name="csv_file" accept=".csv,.txt" required
                       class="w-full px-4 py-3 border-2 border-dashed border-outline-variant rounded-xl text-sm bg-surface-container-lowest cursor-pointer hover:border-primary transition-colors file:mr-3 file:px-4 file:py-2 file:rounded-lg file:border-0 file:bg-primary file:text-on-primary-fixed file:font-bold file:text-xs file:cursor-pointer">
                <p class="text-xs text-on-surface-variant mt-1">
                    <i class="fa-solid fa-circle-info mr-0.5"></i>
                    OTP: Számlatörténet → Letöltés → CSV. CIB: Számlatörténet → Export → CSV.
                </p>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="px-6 py-3 bg-gradient-to-br from-primary to-primary-container text-on-primary-fixed font-bold rounded-full text-sm shadow-lg shadow-primary/10 hover:shadow-primary/20 transition-all flex items-center gap-2">
                    <i class="fa-solid fa-upload"></i> Feltöltés és előnézet
                </button>
                <a href="<?= base_url('/bank-transactions') ?>" class="px-6 py-3 text-on-surface-variant hover:text-on-surface text-sm font-medium">Mégse</a>
            </div>
        </form>
    </div>
</div>
