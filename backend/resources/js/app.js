import './bootstrap';
import * as bootstrap from 'bootstrap';
import htmx from 'htmx.org';

window.htmx = htmx;
window.bootstrap = bootstrap;

htmx.config.refreshOnHistoryMiss = true;

document.addEventListener('htmx:configRequest', e => {
    e.detail.headers['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]')?.content;
});

document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
    new bootstrap.Tooltip(el);
});

document.addEventListener('htmx:afterSwap', () => {
    document.querySelectorAll('.sidebar-icon-link').forEach(link => {
        const url = new URL(link.href, window.location.origin);
        link.classList.toggle('active', url.pathname === window.location.pathname);
    });
});

document.addEventListener('htmx:afterSettle', () => {
    if (window._collapseRestoreAfterSwap) {
        const ids = window._collapseRestoreAfterSwap;
        window._collapseRestoreAfterSwap = null;
        requestAnimationFrame(() => {
            ids.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.classList.add('show');
            });
        });
    }
});
