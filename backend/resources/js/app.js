import './bootstrap';
import * as bootstrap from 'bootstrap';

document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
    new bootstrap.Tooltip(el);
});

document.querySelectorAll('.modal').forEach(modalEl => {
    if (modalEl.querySelector('.alert-danger')) {
        new bootstrap.Modal(modalEl).show();
    }
});
