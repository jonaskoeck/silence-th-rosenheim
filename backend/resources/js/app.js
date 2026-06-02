import './bootstrap';
import * as bootstrap from 'bootstrap';
import htmx from 'htmx.org';

window.htmx = htmx;
window.bootstrap = bootstrap;

htmx.config.refreshOnHistoryMiss = true;

function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;
    const id = 'toast-' + Date.now();
    const bg = type === 'success' ? 'bg-success' : type === 'danger' ? 'bg-danger' : 'bg-warning';
    container.insertAdjacentHTML('beforeend', `
        <div id="${id}" class="toast align-items-center text-white ${bg} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `);
    const el = document.getElementById(id);
    new bootstrap.Toast(el, { delay: 4000 }).show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
}

window.showToast = showToast;

document.addEventListener('htmx:afterRequest', e => {
    const trigger = e.detail.xhr?.getResponseHeader('HX-Trigger');
    if (!trigger) return;
    try {
        const data = JSON.parse(trigger);
        if (data.toast) showToast(data.toast.message, data.toast.type ?? 'success');
    } catch {}
});

document.addEventListener('htmx:responseError', e => {
    const trigger = e.detail.xhr?.getResponseHeader('HX-Trigger');
    if (trigger) {
        try { if (JSON.parse(trigger).toast) return; } catch {}
    }
    showToast('Ein Fehler ist aufgetreten.', 'danger');
});

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

const PENDING_ACTION_PATHS = ['/servers', '/dashboard'];
let pendingActionIds = null;

async function checkPendingActions() {
    if (!PENDING_ACTION_PATHS.includes(window.location.pathname)) {
        pendingActionIds = null;
        return;
    }
    try {
        const res = await fetch('/pending-actions/check', { headers: { 'Accept': 'application/json' } });
        if (!res.ok) return;
        const ids = await res.json();
        if (pendingActionIds === null) {
            pendingActionIds = new Set(ids);
            return;
        }
        const hasNew = ids.some(id => !pendingActionIds.has(id));
        pendingActionIds = new Set(ids);
        if (hasNew) {
            window._collapseRestoreAfterSwap = [...document.querySelectorAll('.collapse.show')].map(el => el.id);
            htmx.ajax('GET', window.location.pathname, { target: '#main-content', swap: 'innerHTML' });
        }
    } catch {}
}

setInterval(checkPendingActions, 5000);
