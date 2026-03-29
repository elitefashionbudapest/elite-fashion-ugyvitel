<?php
use App\Core\Auth;
use App\Models\Invoice;

$stores = $data['stores'] ?? [];
$inputCls = 'w-full px-4 py-3 border border-outline-variant rounded-xl text-sm focus:ring-2 focus:ring-primary-container focus:border-primary bg-surface-container-lowest';
?>

<!-- 2 fő oszlop: BAL form | JOBB előnézet -->
<div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

    <!-- ========== BAL: Számla rögzítés form (3/5) ========== -->
    <div class="lg:col-span-3">
        <div class="bg-surface-container-lowest rounded-xl p-6 sm:p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-container/50 to-primary-container/20 flex items-center justify-center">
                    <i class="fa-solid fa-file-invoice text-on-primary-container"></i>
                </div>
                <div>
                    <h3 class="font-heading font-bold text-on-surface text-xl">Számla rögzítés</h3>
                    <p class="text-xs text-on-surface-variant">Töltse ki az adatokat és csatolja a számlát.</p>
                </div>
            </div>

            <form method="POST" action="<?= base_url('/invoices') ?>" enctype="multipart/form-data" class="space-y-5" id="invoice-form">
                <?= csrf_field() ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- Bal belső oszlop -->
                    <div class="space-y-4">
                        <!-- Beszállító -->
                        <div class="relative">
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Beszállító neve</label>
                            <input type="text" name="supplier_name" id="supplier-input"
                                   value="<?= e(old('supplier_name')) ?>" class="<?= $inputCls ?>"
                                   placeholder="Kezdje el gépelni..." autocomplete="off" required>
                            <div id="supplier-dropdown" class="hidden absolute z-20 left-0 right-0 top-full mt-1 bg-white rounded-xl shadow-xl border border-gray-200 max-h-48 overflow-y-auto"></div>
                        </div>

                        <!-- Számlaszám -->
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Számla száma</label>
                            <input type="text" name="invoice_number" value="<?= e(old('invoice_number')) ?>" class="<?= $inputCls ?>" placeholder="pl. INV-2026-001" required>
                        </div>

                        <!-- Nettó + Bruttó + Pénznem -->
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Nettó összeg</label>
                                <input type="number" name="net_amount" id="net_amount" step="1" min="0" value="<?= e(old('net_amount')) ?>" class="<?= $inputCls ?>" placeholder="0" oninput="calcBrutto()">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Bruttó összeg</label>
                                <input type="number" name="amount" id="brutto_amount" step="1" min="1" value="<?= e(old('amount')) ?>" class="<?= $inputCls ?>" placeholder="0" required oninput="calcNetto()">
                                <p class="text-[10px] text-on-surface-variant mt-0.5" id="vat-info"></p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Pénznem</label>
                                <select name="currency" class="<?= $inputCls ?>">
                                    <?php foreach (Invoice::CURRENCIES as $key => $label): ?>
                                        <option value="<?= $key ?>" <?= old('currency', 'HUF') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Dátumok -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Számla dátuma</label>
                                <input type="date" name="invoice_date" value="<?= e(old('invoice_date', date('Y-m-d'))) ?>" class="<?= $inputCls ?>" required>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Fizetési határidő</label>
                                <input type="date" name="due_date" value="<?= e(old('due_date')) ?>" class="<?= $inputCls ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Jobb belső oszlop -->
                    <div class="space-y-4">
                        <?php if (Auth::isOwner()): ?>
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Bolt</label>
                            <select name="store_id" class="<?= $inputCls ?>" required>
                                <option value="">— Válasszon —</option>
                                <?php foreach ($stores as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= old('store_id') == $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        <!-- Fizetési mód -->
                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-2">Fizetési mód</label>
                            <div class="grid grid-cols-2 gap-2">
                                <?php foreach (Invoice::PAYMENT_METHODS as $key => $label): ?>
                                <label class="flex items-center gap-2 cursor-pointer p-2.5 rounded-lg hover:bg-surface-container-low transition-colors border border-surface-container has-[:checked]:border-primary has-[:checked]:bg-primary-container/10">
                                    <input type="radio" name="payment_method" value="<?= $key ?>"
                                           <?= old('payment_method', 'atutalas') === $key ? 'checked' : '' ?>
                                           class="h-3.5 w-3.5 text-primary border-outline focus:ring-primary-container" required>
                                    <span class="text-xs font-medium text-on-surface"><?= e($label) ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-1.5">Megjegyzés</label>
                            <textarea name="notes" rows="2" class="<?= $inputCls ?> resize-none" placeholder="Opcionális..."><?= e(old('notes')) ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Fotó forrás: feltöltés + kamera -->
                <div class="pt-4 border-t border-surface-container">
                    <label class="block text-xs font-bold text-on-surface-variant uppercase tracking-widest mb-2">Számla csatolása</label>
                    <div class="grid grid-cols-2 gap-3">
                        <!-- Fájl -->
                        <div>
                            <input type="file" name="invoice_image" id="invoice-image" accept="image/*,.pdf" class="hidden" onchange="previewImage(this)">
                            <label for="invoice-image" class="flex items-center justify-center gap-2 w-full px-4 py-3.5 border-2 border-dashed border-outline-variant rounded-xl cursor-pointer hover:border-primary hover:bg-primary-container/5 transition-colors">
                                <i class="fa-solid fa-cloud-arrow-up text-on-surface-variant"></i>
                                <span class="text-xs font-medium text-on-surface-variant" id="file-label">Fájl feltöltése</span>
                            </label>
                        </div>
                        <!-- Kamera -->
                        <button type="button" onclick="startCamera()" id="camera-btn" class="flex items-center justify-center gap-2 w-full px-4 py-3.5 border-2 border-dashed border-blue-300 rounded-xl hover:border-blue-500 hover:bg-blue-50 transition-colors">
                            <i class="fa-solid fa-camera text-blue-500"></i>
                            <span class="text-xs font-medium text-blue-600">Fotó készítése</span>
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-3 border-t border-surface-container">
                    <button type="submit" class="px-6 py-3 bg-gradient-to-br from-primary to-primary-container text-on-primary-fixed font-bold rounded-full text-sm shadow-lg shadow-primary/10 hover:shadow-primary/20 transition-all flex items-center gap-2">
                        <i class="fa-solid fa-check"></i> Rögzítés
                    </button>
                    <a href="<?= base_url('/invoices') ?>" class="text-on-surface-variant hover:text-on-surface text-sm font-medium">Mégse</a>
                </div>
            </form>
        </div>
    </div>

    <!-- ========== JOBB: Előnézet + Kamera (2/5) ========== -->
    <div class="lg:col-span-2">
        <div class="bg-surface-container-lowest rounded-xl p-6 sm:p-8 h-full flex flex-col">
            <div class="flex items-center gap-2 mb-4">
                <i class="fa-solid fa-image text-on-surface-variant"></i>
                <h3 class="font-heading font-bold text-on-surface text-sm">Előnézet</h3>
            </div>

            <!-- Kamera nézet -->
            <div id="camera-active" class="hidden mb-4">
                <div class="rounded-xl overflow-hidden border-2 border-blue-400 bg-black relative">
                    <video id="camera-video" autoplay playsinline class="w-full rounded-xl"></video>
                    <!-- Visszaszámlálás -->
                    <div id="countdown-overlay" class="hidden absolute inset-0 flex items-center justify-center bg-black/40">
                        <span id="countdown-number" class="text-8xl font-heading font-extrabold text-white drop-shadow-lg transition-all"></span>
                    </div>
                    <!-- Villanás -->
                    <div id="flash-overlay" class="hidden absolute inset-0 bg-white"></div>
                </div>
                <!-- Kamera gombok -->
                <div class="flex justify-center gap-2 mt-3">
                    <button type="button" onclick="captureWithCountdown()" class="px-5 py-2.5 bg-blue-600 text-white rounded-full text-xs font-bold hover:bg-blue-700 transition-colors shadow-lg flex items-center gap-1.5">
                        <i class="fa-solid fa-camera"></i> Fotó (10 mp)
                    </button>
                    <button type="button" onclick="captureNow()" class="px-5 py-2.5 bg-emerald-600 text-white rounded-full text-xs font-bold hover:bg-emerald-700 transition-colors shadow-lg flex items-center gap-1.5">
                        <i class="fa-solid fa-bolt"></i> Azonnali
                    </button>
                    <button type="button" onclick="stopCamera()" class="px-4 py-2.5 bg-red-500 text-white rounded-full text-xs font-bold hover:bg-red-600 transition-colors shadow-lg">
                        <i class="fa-solid fa-xmark"></i> Bezárás
                    </button>
                </div>
            </div>
            <canvas id="camera-canvas" class="hidden"></canvas>

            <!-- Előnézet tartalom -->
            <div id="preview-area" class="flex-1 flex flex-col items-center justify-center">
                <!-- Üres állapot -->
                <div id="preview-empty" class="text-center py-8">
                    <div class="w-20 h-20 rounded-2xl bg-surface-container-low mx-auto mb-4 flex items-center justify-center">
                        <i class="fa-solid fa-file-image text-3xl text-gray-300"></i>
                    </div>
                    <p class="text-sm text-gray-400 font-medium">Nincs csatolva számla</p>
                    <p class="text-[10px] text-gray-300 mt-1">Töltsön fel fájlt vagy készítsen fotót</p>
                </div>

                <!-- Kitöltött állapot -->
                <div id="preview-filled" class="hidden w-full">
                    <div class="relative rounded-xl overflow-hidden border border-surface-container bg-white shadow-sm">
                        <img id="preview-img" src="" class="w-full max-h-[300px] object-contain">
                        <!-- Olvashatóság badge -->
                        <div id="readability-badge" class="hidden absolute top-3 right-3">
                            <div id="badge-ok" class="hidden flex items-center gap-1.5 bg-emerald-500 text-white px-3 py-1.5 rounded-full text-xs font-bold shadow-lg">
                                <i class="fa-solid fa-circle-check"></i> Olvasható
                            </div>
                            <div id="badge-bad" class="hidden flex items-center gap-1.5 bg-red-500 text-white px-3 py-1.5 rounded-full text-xs font-bold shadow-lg">
                                <i class="fa-solid fa-circle-xmark"></i> Gyenge minőség
                            </div>
                        </div>
                    </div>

                    <!-- Info + törlés -->
                    <div class="mt-3 flex items-center justify-between">
                        <div>
                            <p id="preview-name" class="text-sm font-medium text-on-surface"></p>
                            <p id="preview-size" class="text-[10px] text-on-surface-variant"></p>
                        </div>
                        <button type="button" onclick="clearPreview()" class="px-3 py-1.5 text-xs text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg font-medium transition-colors">
                            <i class="fa-solid fa-trash-can mr-1"></i> Törlés
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JS -->
<script>
// Beszállító autocomplete
(function() {
    const input = document.getElementById('supplier-input'), dropdown = document.getElementById('supplier-dropdown'), baseUrl = '<?= base_url('') ?>';
    let debounce = null;
    input.addEventListener('input', function() {
        clearTimeout(debounce); const q = this.value.trim();
        if (q.length < 2) { dropdown.classList.add('hidden'); return; }
        debounce = setTimeout(async function() {
            try { const res = await fetch(baseUrl+'/invoices/suppliers?q='+encodeURIComponent(q)); const s = await res.json();
            if (!s.length) { dropdown.classList.add('hidden'); return; }
            let h = ''; s.forEach(x => { h += '<button type="button" class="w-full text-left px-4 py-2.5 hover:bg-surface-container-low transition-colors text-sm" onclick="selectSupplier(\''+x.name.replace(/'/g,"\\'")+'\')">' +
                '<i class="fa-solid fa-truck text-[10px] text-on-surface-variant mr-2"></i>'+escapeHtml(x.name)+'</button>'; });
            dropdown.innerHTML = h; dropdown.classList.remove('hidden'); } catch(e) {}
        }, 300);
    });
    window.selectSupplier = function(n) { input.value = n; dropdown.classList.add('hidden'); };
    document.addEventListener('click', e => { if (!input.contains(e.target) && !dropdown.contains(e.target)) dropdown.classList.add('hidden'); });
    function escapeHtml(s) { const d=document.createElement('div');d.textContent=s||'';return d.innerHTML; }
})();

// Előnézet
function showPreview(file, dataUrl) {
    document.getElementById('preview-empty').classList.add('hidden');
    document.getElementById('preview-filled').classList.remove('hidden');
    const img = document.getElementById('preview-img');
    document.getElementById('preview-name').textContent = file ? file.name : 'Kamera fotó';
    document.getElementById('preview-size').textContent = file ? (file.size / 1024).toFixed(0) + ' KB' : '';

    const src = dataUrl || '';
    if (dataUrl) { img.src = dataUrl; checkReadability(img); }
    else if (file && file.type.startsWith('image/')) {
        const r = new FileReader();
        r.onload = e => { img.src = e.target.result; checkReadability(img); };
        r.readAsDataURL(file);
    }
}
function clearPreview() {
    document.getElementById('preview-empty').classList.remove('hidden');
    document.getElementById('preview-filled').classList.add('hidden');
    document.getElementById('invoice-image').value = '';
    document.getElementById('file-label').textContent = 'Fájl feltöltése';
    document.getElementById('readability-badge').classList.add('hidden');
}
function checkReadability(img) {
    const badge = document.getElementById('readability-badge');
    const ok = document.getElementById('badge-ok');
    const bad = document.getElementById('badge-bad');
    img.onload = function() {
        badge.classList.remove('hidden');
        const isOk = img.naturalWidth >= 400 && img.naturalHeight >= 300;
        if (isOk) { ok.classList.remove('hidden'); bad.classList.add('hidden'); }
        else { bad.classList.remove('hidden'); ok.classList.add('hidden'); }
    };
}
function previewImage(input) {
    if (input.files && input.files[0]) {
        showPreview(input.files[0], null);
        document.getElementById('file-label').textContent = input.files[0].name;
    }
}

// Webkamera
let cameraStream = null, countdownTimer = null;
function startCamera() {
    navigator.mediaDevices.getUserMedia({ video: { facingMode:'environment', width:{ideal:1920}, height:{ideal:1080} } })
    .then(stream => {
        cameraStream = stream;
        document.getElementById('camera-video').srcObject = stream;
        document.getElementById('camera-active').classList.remove('hidden');
        document.getElementById('camera-btn').classList.add('hidden');
    }).catch(err => alert('Kamera hiba: ' + err.message));
}
function stopCamera() {
    if (cameraStream) { cameraStream.getTracks().forEach(t => t.stop()); cameraStream = null; }
    if (countdownTimer) { clearInterval(countdownTimer); countdownTimer = null; }
    document.getElementById('camera-active').classList.add('hidden');
    document.getElementById('camera-btn').classList.remove('hidden');
    document.getElementById('countdown-overlay').classList.add('hidden');
}
function captureWithCountdown() {
    const overlay = document.getElementById('countdown-overlay'), num = document.getElementById('countdown-number');
    overlay.classList.remove('hidden'); let c = 10; num.textContent = c;
    if (countdownTimer) clearInterval(countdownTimer);
    countdownTimer = setInterval(() => {
        c--;
        if (c <= 0) { clearInterval(countdownTimer); countdownTimer=null; overlay.classList.add('hidden'); takePhoto(); }
        else { num.textContent = c; num.style.color = c <= 3 ? '#f87171' : 'white'; num.style.transform = c <= 3 ? 'scale(1.3)' : ''; }
    }, 1000);
}
function captureNow() {
    if (countdownTimer) { clearInterval(countdownTimer); countdownTimer=null; }
    document.getElementById('countdown-overlay').classList.add('hidden'); takePhoto();
}
function takePhoto() {
    const v = document.getElementById('camera-video'), c = document.getElementById('camera-canvas'), f = document.getElementById('flash-overlay');
    f.classList.remove('hidden'); setTimeout(()=>f.classList.add('hidden'), 150);
    c.width = v.videoWidth; c.height = v.videoHeight;
    c.getContext('2d').drawImage(v, 0, 0);
    c.toBlob(blob => {
        if (!blob) return;
        const file = new File([blob], 'szamla_foto_'+Date.now()+'.jpg', {type:'image/jpeg'});
        const dt = new DataTransfer(); dt.items.add(file);
        document.getElementById('invoice-image').files = dt.files;
        showPreview(file, c.toDataURL('image/jpeg', 0.9));
        document.getElementById('file-label').textContent = 'Fotó készítve!';
        try { const ac=new AudioContext(),o=ac.createOscillator(),g=ac.createGain(); o.connect(g);g.connect(ac.destination);o.frequency.value=1000;o.type='sine';g.gain.setValueAtTime(0.2,ac.currentTime);g.gain.exponentialRampToValueAtTime(0.01,ac.currentTime+0.15);o.start(ac.currentTime);o.stop(ac.currentTime+0.15); } catch(e){}
        stopCamera();
    }, 'image/jpeg', 0.9);
}

// Nettó-Bruttó auto kalkuláció (27% ÁFA)
let vatCalcDirection = null;
function calcBrutto() {
    const net = parseFloat(document.getElementById('net_amount').value) || 0;
    if (net > 0 && vatCalcDirection !== 'fromBrutto') {
        vatCalcDirection = 'fromNetto';
        const brutto = Math.round(net * 1.27);
        document.getElementById('brutto_amount').value = brutto;
        updateVatInfo(net, brutto);
    }
    if (net === 0) vatCalcDirection = null;
}
function calcNetto() {
    const brutto = parseFloat(document.getElementById('brutto_amount').value) || 0;
    const net = parseFloat(document.getElementById('net_amount').value) || 0;
    if (brutto > 0 && vatCalcDirection !== 'fromNetto') {
        vatCalcDirection = 'fromBrutto';
        // Csak ha a nettó üres, számoljuk ki automatikusan
        if (!net) {
            const calcNet = Math.round(brutto / 1.27);
            document.getElementById('net_amount').value = calcNet;
            updateVatInfo(calcNet, brutto);
        } else {
            updateVatInfo(net, brutto);
        }
    }
    if (brutto === 0) vatCalcDirection = null;
}
function updateVatInfo(net, brutto) {
    const vat = brutto - net;
    const pct = net > 0 ? Math.round((vat / net) * 100) : 0;
    document.getElementById('vat-info').textContent = vat > 0 ? 'ÁFA: ' + new Intl.NumberFormat('hu-HU').format(vat) + ' Ft (' + pct + '%)' : (net === brutto ? '0% ÁFA (külföldi)' : '');
}
</script>
