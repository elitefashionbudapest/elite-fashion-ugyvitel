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
