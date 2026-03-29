/**
 * Elite Fashion - Vonalkod szkenner modul
 * QuaggaJS alapu kamera szkenner + manualis bevitel
 */

(function () {
    'use strict';

    // --- Allapot ---
    let scannerRunning = false;
    let lastScannedCode = '';
    let lastScannedTime = 0;
    const DEBOUNCE_MS = 3000; // 3 masodperc ugyanarra a kodra

    // --- DOM elemek ---
    const scannerContainer = document.getElementById('scanner-container');
    const scannerPlaceholder = document.getElementById('scanner-placeholder');
    const scannerOverlay = document.getElementById('scanner-overlay');
    const btnStart = document.getElementById('btn-start-scanner');
    const btnStop = document.getElementById('btn-stop-scanner');
    const manualInput = document.getElementById('manual-barcode');
    const lastScannedDiv = document.getElementById('last-scanned');
    const lastScannedCodeEl = document.getElementById('last-scanned-code');
    const scannerError = document.getElementById('scanner-error');
    const scannerErrorText = document.getElementById('scanner-error-text');
    const soundToggle = document.getElementById('sound-toggle');
    const itemCountEl = document.getElementById('item-count');
    const defectTbody = document.getElementById('defect-tbody');
    const emptyRow = document.getElementById('empty-row');
    const storeSelector = document.getElementById('store-selector');
    const isOwner = document.getElementById('is-owner')?.value === '1';

    // --- Hang (beep) ---
    let audioCtx = null;

    function playBeep() {
        if (!soundToggle || !soundToggle.checked) return;
        try {
            if (!audioCtx) {
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
            const oscillator = audioCtx.createOscillator();
            const gainNode = audioCtx.createGain();
            oscillator.connect(gainNode);
            gainNode.connect(audioCtx.destination);
            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(1200, audioCtx.currentTime);
            gainNode.gain.setValueAtTime(0.3, audioCtx.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.15);
            oscillator.start(audioCtx.currentTime);
            oscillator.stop(audioCtx.currentTime + 0.15);
        } catch (e) {
            // Hang nem tamogatott - csendben tovabbmegyunk
        }
    }

    // --- Hiba megjelenites ---
    function showError(msg) {
        if (scannerError && scannerErrorText) {
            scannerErrorText.textContent = msg;
            scannerError.classList.remove('hidden');
        }
    }

    function hideError() {
        if (scannerError) {
            scannerError.classList.add('hidden');
        }
    }

    // --- Szkenner inditas ---
    window.startScanner = function () {
        if (scannerRunning) return;
        hideError();

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showError('A bongeszoje nem tamogatja a kamera hasznalatat. Hasznaljon manualis bevitelt.');
            return;
        }

        Quagga.init({
            inputStream: {
                name: 'Live',
                type: 'LiveStream',
                target: scannerContainer,
                constraints: {
                    facingMode: 'environment',
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                },
            },
            decoder: {
                readers: [
                    'ean_reader',
                    'ean_8_reader',
                    'code_128_reader',
                    'code_39_reader',
                    'upc_reader',
                    'upc_e_reader',
                ],
            },
            locate: true,
            frequency: 10,
        }, function (err) {
            if (err) {
                console.error('Quagga init error:', err);
                showError('Kamera nem elerheto. Hasznaljon manualis bevitelt. (' + (err.message || err) + ')');
                return;
            }
            Quagga.start();
            scannerRunning = true;

            // Quagga letrehozza a sajat video elemet a containerben
            if (scannerPlaceholder) scannerPlaceholder.classList.add('hidden');
            if (scannerOverlay) scannerOverlay.classList.remove('hidden');
            if (btnStart) btnStart.classList.add('hidden');
            if (btnStop) btnStop.classList.remove('hidden');
        });

        Quagga.onDetected(onBarcodeDetected);
    };

    // --- Szkenner leallitas ---
    window.stopScanner = function () {
        if (!scannerRunning) return;
        Quagga.stop();
        Quagga.offDetected(onBarcodeDetected);
        scannerRunning = false;

        if (scannerPlaceholder) scannerPlaceholder.classList.remove('hidden');
        if (scannerOverlay) scannerOverlay.classList.add('hidden');
        if (btnStart) btnStart.classList.remove('hidden');
        if (btnStop) btnStop.classList.add('hidden');

        // Quagga altal letrehozott video elemek eltavolitasa
        var videos = scannerContainer.querySelectorAll('video');
        videos.forEach(function (v) { v.srcObject = null; });
        var canvases = scannerContainer.querySelectorAll('canvas');
        canvases.forEach(function (c) { c.remove(); });
    };

    // --- Vonalkod eszleles callback ---
    function onBarcodeDetected(result) {
        var code = result.codeResult.code;
        if (!code) return;

        // Debounce: ugyanaz a kod 3 masodpercen belul
        var now = Date.now();
        if (code === lastScannedCode && (now - lastScannedTime) < DEBOUNCE_MS) {
            return;
        }

        lastScannedCode = code;
        lastScannedTime = now;

        playBeep();
        submitBarcode(code);
    }

    // --- Manualis bevitel ---
    window.submitManualBarcode = function () {
        var code = manualInput ? manualInput.value.trim() : '';
        if (!code) return;
        submitBarcode(code);
        if (manualInput) manualInput.value = '';
    };

    // Enter billentyuvel is kuldheto
    if (manualInput) {
        manualInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                submitManualBarcode();
            }
        });
    }

    // --- Vonalkod kuldese a szerverre ---
    function submitBarcode(barcode) {
        hideError();
        var scanUrl = document.getElementById('scan-url')?.value || '/defects/scan';

        var body = { barcode: barcode };

        // Tulajdonos: bolt kivalasztas
        if (isOwner && storeSelector) {
            var selectedStore = storeSelector.value;
            if (!selectedStore) {
                showError('Kerem valasszon boltot a szkenneleshez!');
                return;
            }
            body.store_id = parseInt(selectedStore, 10);
        }

        fetchWithCsrf(scanUrl, {
            method: 'POST',
            body: JSON.stringify(body),
        })
        .then(function (data) {
            if (data.success && data.item) {
                addRowToTable(data.item);
                showLastScanned(data.item.barcode);
                updateItemCount(1);
                playBeep();
            } else {
                showError(data.error || 'Ismeretlen hiba tortent.');
            }
        })
        .catch(function (err) {
            console.error('Scan error:', err);
            showError('Halozati hiba. Kerem problja ujra. (' + err.message + ')');
        });
    }

    // --- Sor hozzaadasa a tablazathoz ---
    function addRowToTable(item) {
        // Ures sor eltavolitasa
        if (emptyRow) emptyRow.remove();

        var tr = document.createElement('tr');
        tr.setAttribute('data-id', item.id);

        var scannedAt = item.scanned_at;
        // Datum formalizalas
        try {
            var d = new Date(scannedAt);
            scannedAt = d.getFullYear() + '.' +
                String(d.getMonth() + 1).padStart(2, '0') + '.' +
                String(d.getDate()).padStart(2, '0') + ' ' +
                String(d.getHours()).padStart(2, '0') + ':' +
                String(d.getMinutes()).padStart(2, '0');
        } catch (e) {
            // eredeti formatum megtartasa
        }

        var html = '<td class="font-mono font-medium">' + escapeHtml(item.barcode) + '</td>' +
                   '<td>' + escapeHtml(item.store_name) + '</td>' +
                   '<td class="whitespace-nowrap text-sm text-gray-600">' + escapeHtml(scannedAt) + '</td>';

        if (isOwner) {
            html += '<td class="text-right whitespace-nowrap">' +
                    '<span class="text-gray-400 text-xs">Uj</span>' +
                    '</td>';
        }

        tr.innerHTML = html;

        // Sor beszurasa a tablazat elejere
        if (defectTbody && defectTbody.firstChild) {
            defectTbody.insertBefore(tr, defectTbody.firstChild);
        } else if (defectTbody) {
            defectTbody.appendChild(tr);
        }

        // Sor kiemelese animacioval
        tr.style.backgroundColor = '#f0fdf4';
        setTimeout(function () {
            tr.style.transition = 'background-color 1s';
            tr.style.backgroundColor = '';
        }, 100);
    }

    // --- Utolso szkennelt kod megjelenites ---
    function showLastScanned(code) {
        if (lastScannedDiv && lastScannedCodeEl) {
            lastScannedCodeEl.textContent = code;
            lastScannedDiv.classList.remove('hidden');
        }
    }

    // --- Darabszam frissites ---
    function updateItemCount(delta) {
        if (!itemCountEl) return;
        var text = itemCountEl.textContent || '0 db';
        var num = parseInt(text, 10) || 0;
        num += delta;
        itemCountEl.textContent = num + ' db';
    }

    // --- HTML escape ---
    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

})();
