/**
 * Elite Fashion Ügyviteli Rendszer - JavaScript
 */

// ==========================================
// Sidebar toggle (mobil)
// ==========================================
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
}

// ESC billentyűvel sidebar bezárás
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const sidebar = document.getElementById('sidebar');
        if (sidebar && !sidebar.classList.contains('-translate-x-full')) {
            toggleSidebar();
        }
    }
});

// ==========================================
// CSRF token helper (AJAX kérésekhez)
// ==========================================
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

// ==========================================
// Flash üzenetek automatikus eltűntetése
// ==========================================
document.addEventListener('DOMContentLoaded', function() {
    const flashMessages = document.querySelectorAll('[id^="flash-"]');
    flashMessages.forEach(function(msg) {
        setTimeout(function() {
            msg.style.transition = 'opacity 0.3s, transform 0.3s';
            msg.style.opacity = '0';
            msg.style.transform = 'translateY(-10px)';
            setTimeout(function() { msg.remove(); }, 300);
        }, 5000);
    });
});

// ==========================================
// Confirm delete (törlés megerősítés)
// ==========================================
function confirmDelete(form, itemName) {
    if (confirm('Biztosan törölni szeretné: ' + itemName + '?')) {
        form.submit();
    }
    return false;
}

// ==========================================
// Fetch wrapper (CSRF tokennel)
// ==========================================
async function fetchWithCsrf(url, options = {}) {
    const defaults = {
        headers: {
            'X-CSRF-TOKEN': getCsrfToken(),
            'Content-Type': 'application/json',
        },
    };

    const merged = {
        ...defaults,
        ...options,
        headers: { ...defaults.headers, ...(options.headers || {}) },
    };

    const response = await fetch(url, merged);

    if (!response.ok) {
        throw new Error('HTTP error: ' + response.status);
    }

    return response.json();
}

// ==========================================
// Összeg kalkulátor (pl. 15000+23000+8500 = 46500)
// ==========================================
// ==========================================
// Táblázat rendezés (kattintás a fejlécre)
// ==========================================
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('table').forEach(function(table) {
        var headers = table.querySelectorAll('thead th');
        if (headers.length === 0) return;

        headers.forEach(function(th, colIndex) {
            // Checkbox oszlopot és üres fejlécet kihagyjuk
            if (th.querySelector('input[type="checkbox"]') || th.textContent.trim() === '') return;

            th.style.cursor = 'pointer';
            th.style.userSelect = 'none';
            th.setAttribute('title', 'Kattints a rendezéshez');

            // Rendezés ikon
            var icon = document.createElement('i');
            icon.className = 'fa-solid fa-sort sort-icon';
            icon.style.marginLeft = '4px';
            icon.style.fontSize = '9px';
            icon.style.opacity = '0.3';
            th.appendChild(icon);

            th.addEventListener('click', function() {
                var tbody = table.querySelector('tbody');
                if (!tbody) return;

                var rows = Array.from(tbody.querySelectorAll('tr'));
                // Üres/placeholder sorokat kihagyjuk a rendezésből
                rows = rows.filter(function(r) { return r.querySelectorAll('td').length > 1 || !r.querySelector('[colspan]'); });
                if (rows.length < 2) return;

                // Jelenlegi irány
                var asc = th.dataset.sortDir !== 'asc';
                th.dataset.sortDir = asc ? 'asc' : 'desc';

                // Ikonok resetelése
                headers.forEach(function(h) {
                    var si = h.querySelector('.sort-icon');
                    if (si) { si.className = 'fa-solid fa-sort sort-icon'; si.style.opacity = '0.3'; }
                });
                icon.className = asc ? 'fa-solid fa-sort-up sort-icon' : 'fa-solid fa-sort-down sort-icon';
                icon.style.opacity = '1';

                rows.sort(function(a, b) {
                    var cellA = a.querySelectorAll('td')[colIndex];
                    var cellB = b.querySelectorAll('td')[colIndex];
                    if (!cellA || !cellB) return 0;

                    var valA = (cellA.dataset.sortValue !== undefined) ? cellA.dataset.sortValue : cellA.textContent.trim();
                    var valB = (cellB.dataset.sortValue !== undefined) ? cellB.dataset.sortValue : cellB.textContent.trim();

                    // Szám felismerés (Ft, %, szóközök eltávolítása)
                    var numA = parseFloat(valA.replace(/[^\d.,-]/g, '').replace(/\s/g, '').replace(',', '.'));
                    var numB = parseFloat(valB.replace(/[^\d.,-]/g, '').replace(/\s/g, '').replace(',', '.'));

                    if (!isNaN(numA) && !isNaN(numB)) {
                        return asc ? numA - numB : numB - numA;
                    }

                    // Dátum felismerés (YYYY.MM.DD vagy YYYY-MM-DD)
                    var dateA = Date.parse(valA.replace(/\./g, '-').replace(/\s/g, ''));
                    var dateB = Date.parse(valB.replace(/\./g, '-').replace(/\s/g, ''));
                    if (!isNaN(dateA) && !isNaN(dateB)) {
                        return asc ? dateA - dateB : dateB - dateA;
                    }

                    // Szöveges rendezés (magyar ékezetek)
                    return asc
                        ? valA.localeCompare(valB, 'hu')
                        : valB.localeCompare(valA, 'hu');
                });

                rows.forEach(function(row) { tbody.appendChild(row); });
            });
        });
    });
});

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('input[data-calc]').forEach(function(input) {
        input.addEventListener('blur', function() {
            const val = this.value.trim();
            if (val.includes('+')) {
                const parts = val.split('+').map(function(p) {
                    return parseFloat(p.replace(/\s/g, '').replace(',', '.')) || 0;
                });
                const sum = parts.reduce(function(a, b) { return a + b; }, 0);
                this.value = sum % 1 === 0 ? sum : sum.toFixed(2);
                // Triggerelünk input/change eventeket hogy a többi logika is fusson
                this.dispatchEvent(new Event('input', { bubbles: true }));
                this.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    });
});
