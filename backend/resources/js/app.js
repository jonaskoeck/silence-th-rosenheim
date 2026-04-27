import './bootstrap';
import * as bootstrap from 'bootstrap';

document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
    new bootstrap.Tooltip(el);
});
