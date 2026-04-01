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
                <button type="submit" id="submit-btn" class="px-6 py-3 bg-gradient-to-br from-primary to-primary-container text-on-primary-fixed font-bold rounded-full text-sm shadow-lg shadow-primary/10 hover:shadow-primary/20 transition-all flex items-center gap-2">
                    <i class="fa-solid fa-upload"></i> Feltöltés és feldolgozás
                </button>
                <a href="<?= base_url('/invoices') ?>" class="px-6 py-3 text-on-surface-variant hover:text-on-surface text-sm font-medium">Mégse</a>
            </div>
        </form>
    </div>
</div>

<!-- AI feldolgozás overlay -->
<div id="ai-overlay" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden flex items-center justify-center">
    <div class="bg-surface-container-lowest rounded-2xl p-8 shadow-2xl max-w-sm w-full mx-4 text-center">
        <div class="relative w-16 h-16 mx-auto mb-4">
            <div class="absolute inset-0 rounded-full border-4 border-primary/20"></div>
            <div class="absolute inset-0 rounded-full border-4 border-primary border-t-transparent animate-spin"></div>
            <div class="absolute inset-3 rounded-full border-4 border-blue-400 border-b-transparent animate-spin" style="animation-direction:reverse;animation-duration:1.5s"></div>
        </div>
        <h3 class="font-heading font-bold text-on-surface text-lg mb-1">AI feldolgozás</h3>
        <p class="text-sm text-on-surface-variant mb-3" id="ai-status">Számlák feltöltése...</p>
        <div class="w-full bg-surface-container rounded-full h-2 mb-2">
            <div id="ai-progress" class="bg-gradient-to-r from-primary to-blue-500 h-2 rounded-full transition-all duration-500" style="width: 0%"></div>
        </div>
        <p class="text-xs text-on-surface-variant" id="ai-detail">
            <span id="ai-current">0</span> / <span id="ai-total">0</span> számla
        </p>
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

document.querySelector('form').addEventListener('submit', function() {
    const fileCount = document.getElementById('file-input').files.length;
    if (fileCount === 0) return;

    // Overlay megjelenítése
    const overlay = document.getElementById('ai-overlay');
    overlay.classList.remove('hidden');
    document.getElementById('ai-total').textContent = fileCount;
    document.getElementById('submit-btn').disabled = true;

    // Animált progress szimuláció
    const messages = [
        'Számlák feltöltése...',
        'AI elemzi a számlákat...',
        'Beszállítók felismerése...',
        'Összegek kinyerése...',
        'Duplikátumok ellenőrzése...',
        'Adatok mentése...',
    ];

    let step = 0;
    let progress = 0;
    const totalTime = fileCount * 8; // kb 8 mp / számla
    const interval = setInterval(function() {
        step++;
        progress = Math.min(95, (step / (totalTime / 2)) * 100);

        document.getElementById('ai-progress').style.width = progress + '%';

        const currentFile = Math.min(Math.ceil((progress / 100) * fileCount), fileCount);
        document.getElementById('ai-current').textContent = currentFile;

        const msgIdx = Math.min(Math.floor(progress / 20), messages.length - 1);
        document.getElementById('ai-status').textContent = messages[msgIdx];
    }, 2000);
});
</script>
