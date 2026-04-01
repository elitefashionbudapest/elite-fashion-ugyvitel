<?php
$inputCls = 'w-full px-4 py-3 border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary-container focus:border-primary bg-surface-container-lowest';
?>

<div class="max-w-2xl">
    <div class="bg-surface-container-lowest rounded-xl p-6 sm:p-8">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                <i class="fa-solid fa-file-arrow-up text-blue-600"></i>
            </div>
            <div>
                <h3 class="font-heading font-bold text-on-surface text-xl">Számlák tömeges feltöltése</h3>
                <p class="text-xs text-on-surface-variant">Több számla PDF feltöltése egyszerre. A beszállítót, összeget és dátumot automatikusan felismeri.</p>
            </div>
        </div>

        <form method="POST" action="<?= base_url('/invoices/bulk-upload') ?>" enctype="multipart/form-data" class="space-y-5">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Számla fájlok (PDF)</label>
                <input type="file" name="invoices[]" accept=".pdf" required multiple
                       class="w-full px-4 py-3 border-2 border-dashed border-outline-variant rounded-xl text-sm bg-surface-container-lowest cursor-pointer hover:border-primary transition-colors file:mr-3 file:px-4 file:py-2 file:rounded-lg file:border-0 file:bg-primary file:text-on-primary-fixed file:font-bold file:text-xs file:cursor-pointer"
                       id="file-input">
                <p class="text-xs text-on-surface-variant mt-1">
                    <i class="fa-solid fa-circle-info mr-0.5"></i>
                    Több fájl kijelölhető egyszerre (Ctrl+kattintás). A beszállítót a számla tartalmából ismeri fel.
                </p>
                <div id="file-count" class="text-xs font-bold text-primary mt-1 hidden"></div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="px-6 py-3 bg-gradient-to-br from-primary to-primary-container text-on-primary-fixed font-bold rounded-full text-sm shadow-lg shadow-primary/10 hover:shadow-primary/20 transition-all flex items-center gap-2">
                    <i class="fa-solid fa-upload"></i> Feltöltés és feldolgozás
                </button>
                <a href="<?= base_url('/invoices') ?>" class="px-6 py-3 text-on-surface-variant hover:text-on-surface text-sm font-medium">Mégse</a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('file-input').addEventListener('change', function() {
    const count = this.files.length;
    const el = document.getElementById('file-count');
    if (count > 0) {
        el.textContent = count + ' fájl kiválasztva';
        el.classList.remove('hidden');
    } else {
        el.classList.add('hidden');
    }
});
</script>
