import './bootstrap';
import * as bootstrap from 'bootstrap';
import TomSelect from 'tom-select';
import 'tom-select/dist/css/tom-select.bootstrap5.css';

window.TomSelect = TomSelect;

document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
    new bootstrap.Tooltip(el);
});

document.querySelectorAll('.modal').forEach(modalEl => {
    if (modalEl.querySelector('.alert-danger')) {
        new bootstrap.Modal(modalEl).show();
    }
});
