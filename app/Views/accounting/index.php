<?php
use App\Core\Auth;
$year = $data['year'] ?? date('Y');
$month = $data['month'] ?? null;
$statements = $data['statements'] ?? [];
$payslips = $data['payslips'] ?? [];
$banks = $data['banks'] ?? [];
$employees = $data['employees'] ?? [];
$invDateFrom = $data['invDateFrom'] ?? '';
$invDateTo = $data['invDateTo'] ?? '';
$isOwner = Auth::isOwner();
$months = ['','Január','Február','Március','Április','Május','Június','Július','Augusztus','Szeptember','Október','November','December'];
$inputCls = 'px-3 py-2 border border-outline-variant rounded-lg text-sm focus:ring-2 focus:ring-primary-container focus:border-primary bg-surface-container-lowest';
?>

<div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-6">
    <div>
        <h1 class="text-3xl font-heading font-extrabold text-on-surface tracking-tight mb-1">Könyvelői dokumentumok</h1>
        <p class="text-on-surface-variant text-sm">Bankszámlakivonatok, számlák, bérpapírok — dokumentumcsere a könyvelővel.</p>
    </div>
    <form method="GET" action="<?= base_url('/accounting') ?>" class="flex items-end gap-2">
        <div>
            <label class="block text-[10px] font-bold text-on-surface-variant uppercase mb-1">Év</label>
            <select name="year" class="<?= $inputCls ?>">
                <?php for ($y = date('Y'); $y >= 2024; $y--): ?>
                    <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div>
            <label class="block text-[10px] font-bold text-on-surface-variant uppercase mb-1">Hónap</label>
            <select name="month" class="<?= $inputCls ?>">
                <option value="">Mind</option>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $month == $m ? 'selected' : '' ?>><?= $months[$m] ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <button type="submit" class="px-4 py-2 bg-sidebar text-primary rounded-lg text-sm font-bold">Szűrés</button>
    </form>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    <!-- 1. Bankszámlakivonatok -->
    <div class="bg-surface-container-lowest rounded-xl overflow-hidden">
        <div class="px-5 py-3 border-b border-surface-container bg-surface-container-low/50">
            <h2 class="font-heading font-bold text-on-surface text-sm flex items-center gap-2">
                <i class="fa-solid fa-file-lines text-blue-600"></i> Bankszámlakivonatok
            </h2>
        </div>

        <?php if ($isOwner): ?>
        <form id="statement-form" method="POST" action="<?= base_url('/accounting/statement') ?>" enctype="multipart/form-data" class="px-4 py-3 border-b border-surface-container">
            <?= csrf_field() ?>
            <div class="flex flex-wrap gap-2 items-end mb-2">
                <select name="bank_id" class="<?= $inputCls ?> text-xs flex-1" required>
                    <option value="">Bank</option>
                    <?php foreach ($banks as $b): ?>
                        <option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="year" class="<?= $inputCls ?> text-xs w-20">
                    <?php for ($y = date('Y'); $y >= 2024; $y--): ?>
                        <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
                <select name="month" class="<?= $inputCls ?> text-xs w-28" required>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= date('m') == $m ? 'selected' : '' ?>><?= $months[$m] ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="dropzone" id="statement-drop" data-input="statement-file">
                <input type="file" name="statement_file" id="statement-file" accept=".pdf,.jpg,.jpeg,.png" class="hidden" required>
                <div class="dropzone-content">
                    <i class="fa-solid fa-cloud-arrow-up text-2xl text-blue-400"></i>
                    <p class="text-xs text-on-surface-variant mt-1">Húzd ide a fájlt vagy <span class="text-primary font-bold cursor-pointer underline">kattints</span></p>
                    <p class="text-[10px] text-on-surface-variant/60">PDF, JPG, PNG — max 20MB</p>
                </div>
                <div class="dropzone-selected hidden">
                    <i class="fa-solid fa-file-circle-check text-emerald-500 text-lg"></i>
                    <span class="text-xs font-medium text-on-surface dropzone-filename"></span>
                    <button type="submit" class="ml-auto px-3 py-1.5 bg-primary text-on-primary-fixed text-xs font-bold rounded-lg"><i class="fa-solid fa-upload mr-1"></i>Feltöltés</button>
                </div>
            </div>
        </form>
        <?php endif; ?>

        <div class="divide-y divide-surface-container max-h-64 overflow-y-auto">
            <?php if (empty($statements)): ?>
                <div class="px-5 py-6 text-center text-on-surface-variant text-sm">Nincs kivonat.</div>
            <?php else: ?>
                <?php foreach ($statements as $s): ?>
                <div class="px-5 py-2.5 flex items-center gap-3 hover:bg-surface-container-low/50">
                    <i class="fa-solid fa-file-pdf text-red-500"></i>
                    <div class="flex-1 min-w-0">
                        <span class="text-sm font-medium truncate block"><?= e($s['bank_name']) ?></span>
                        <span class="text-[10px] text-on-surface-variant"><?= $s['year'] ?>. <?= $months[$s['month']] ?></span>
                    </div>
                    <a href="<?= base_url($s['file_path']) ?>" target="_blank" class="p-1.5 text-primary hover:text-primary/80 text-xs"><i class="fa-solid fa-download"></i></a>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- 2. Bejövő számlák letöltése -->
    <div class="bg-surface-container-lowest rounded-xl overflow-hidden">
        <div class="px-5 py-3 border-b border-surface-container bg-surface-container-low/50">
            <h2 class="font-heading font-bold text-on-surface text-sm flex items-center gap-2">
                <i class="fa-solid fa-file-invoice text-emerald-600"></i> Bejövő számlák letöltése
            </h2>
        </div>
        <div class="px-4 py-3">
            <p class="text-xs text-on-surface-variant mb-2">Teljesítési dátum szerint szűrve — Excel lista + számla fotók ZIP-ben.</p>
            <form method="GET" action="<?= base_url('/accounting/invoices/download') ?>" class="flex flex-wrap gap-2 items-end">
                <div class="flex-1">
                    <label class="block text-[10px] font-bold text-on-surface-variant uppercase mb-1">Mettől</label>
                    <input type="date" name="date_from" value="<?= e($invDateFrom ?: date('Y-m-01')) ?>" class="<?= $inputCls ?> w-full text-xs">
                </div>
                <div class="flex-1">
                    <label class="block text-[10px] font-bold text-on-surface-variant uppercase mb-1">Meddig</label>
                    <input type="date" name="date_to" value="<?= e($invDateTo ?: date('Y-m-t')) ?>" class="<?= $inputCls ?> w-full text-xs">
                </div>
                <button type="submit" class="px-3 py-2 bg-emerald-600 text-white text-xs font-bold rounded-lg flex items-center gap-1"><i class="fa-solid fa-download"></i> ZIP</button>
            </form>
        </div>
    </div>

    <!-- 3. Bérpapírok -->
    <div class="bg-surface-container-lowest rounded-xl overflow-hidden">
        <div class="px-5 py-3 border-b border-surface-container bg-surface-container-low/50">
            <h2 class="font-heading font-bold text-on-surface text-sm flex items-center gap-2">
                <i class="fa-solid fa-file-contract text-amber-600"></i> Bérpapírok
            </h2>
        </div>

        <?php if (Auth::isAccountant() || $isOwner): ?>
        <form id="payslip-form" method="POST" action="<?= base_url('/accounting/payslip') ?>" enctype="multipart/form-data" class="px-4 py-3 border-b border-surface-container">
            <?= csrf_field() ?>
            <div class="flex flex-wrap gap-2 items-end mb-2">
                <select name="employee_id" class="<?= $inputCls ?> text-xs flex-1" required>
                    <option value="">Dolgozó</option>
                    <?php foreach ($employees as $e): ?>
                        <option value="<?= $e['id'] ?>"><?= e($e['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="year" class="<?= $inputCls ?> text-xs w-20">
                    <?php for ($y = date('Y'); $y >= 2024; $y--): ?>
                        <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
                <select name="month" class="<?= $inputCls ?> text-xs w-28" required>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= date('m') == $m ? 'selected' : '' ?>><?= $months[$m] ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="dropzone" id="payslip-drop" data-input="payslip-file">
                <input type="file" name="payslip_file" id="payslip-file" accept=".pdf,.jpg,.jpeg,.png" class="hidden" required>
                <div class="dropzone-content">
                    <i class="fa-solid fa-cloud-arrow-up text-2xl text-amber-400"></i>
                    <p class="text-xs text-on-surface-variant mt-1">Húzd ide a fájlt vagy <span class="text-primary font-bold cursor-pointer underline">kattints</span></p>
                    <p class="text-[10px] text-on-surface-variant/60">PDF, JPG, PNG — max 20MB</p>
                </div>
                <div class="dropzone-selected hidden">
                    <i class="fa-solid fa-file-circle-check text-emerald-500 text-lg"></i>
                    <span class="text-xs font-medium text-on-surface dropzone-filename"></span>
                    <button type="submit" class="ml-auto px-3 py-1.5 bg-primary text-on-primary-fixed text-xs font-bold rounded-lg"><i class="fa-solid fa-upload mr-1"></i>Feltöltés</button>
                </div>
            </div>
        </form>
        <?php endif; ?>

        <div class="divide-y divide-surface-container max-h-64 overflow-y-auto">
            <?php if (empty($payslips)): ?>
                <div class="px-5 py-6 text-center text-on-surface-variant text-sm">Nincs bérpapír.</div>
            <?php else: ?>
                <?php foreach ($payslips as $p): ?>
                <div class="px-5 py-2.5 flex items-center gap-3 hover:bg-surface-container-low/50">
                    <i class="fa-solid fa-file-pdf text-red-500"></i>
                    <div class="flex-1 min-w-0">
                        <span class="text-sm font-medium truncate block"><?= e($p['employee_name']) ?></span>
                        <span class="text-[10px] text-on-surface-variant"><?= $p['year'] ?>. <?= $months[$p['month']] ?></span>
                    </div>
                    <a href="<?= base_url($p['file_path']) ?>" target="_blank" class="p-1.5 text-primary hover:text-primary/80 text-xs"><i class="fa-solid fa-download"></i></a>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<style>
.dropzone {
    border: 2px dashed rgba(0,0,0,0.15);
    border-radius: 0.75rem;
    padding: 1rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
}
.dropzone:hover, .dropzone.drag-over {
    border-color: #506300;
    background: rgba(80,99,0,0.04);
}
.dropzone.drag-over {
    border-color: #506300;
    background: rgba(80,99,0,0.08);
    transform: scale(1.01);
}
.dropzone-selected {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
</style>

<script>
document.querySelectorAll('.dropzone').forEach(zone => {
    const inputId = zone.dataset.input;
    const fileInput = document.getElementById(inputId);
    const contentEl = zone.querySelector('.dropzone-content');
    const selectedEl = zone.querySelector('.dropzone-selected');
    const filenameEl = zone.querySelector('.dropzone-filename');

    // Kattintás → fájl választó
    zone.addEventListener('click', (e) => {
        if (e.target.closest('button')) return;
        fileInput.click();
    });

    // Fájl kiválasztás
    fileInput.addEventListener('change', () => {
        if (fileInput.files.length > 0) showSelected(fileInput.files[0].name);
    });

    // Drag events
    zone.addEventListener('dragover', (e) => { e.preventDefault(); zone.classList.add('drag-over'); });
    zone.addEventListener('dragleave', () => { zone.classList.remove('drag-over'); });
    zone.addEventListener('drop', (e) => {
        e.preventDefault();
        zone.classList.remove('drag-over');
        if (e.dataTransfer.files.length > 0) {
            const dt = new DataTransfer();
            dt.items.add(e.dataTransfer.files[0]);
            fileInput.files = dt.files;
            showSelected(e.dataTransfer.files[0].name);
        }
    });

    function showSelected(name) {
        contentEl.classList.add('hidden');
        selectedEl.classList.remove('hidden');
        filenameEl.textContent = name;
    }
});
</script>
