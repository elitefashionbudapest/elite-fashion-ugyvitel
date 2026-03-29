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
