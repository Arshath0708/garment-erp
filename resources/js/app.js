import './bootstrap';

import 'bootstrap';
import 'admin-lte/dist/js/adminlte.js';

import ApexCharts from 'apexcharts';
window.ApexCharts = ApexCharts;

import TomSelect from 'tom-select';
window.TomSelect = TomSelect;

/**
 * Searchable dropdowns.
 *
 * The master sheets ask for "drop down with a search bar" on category, unit,
 * price band, GST rate, PO format and calculated-on. A native <select> has
 * type-ahead but no search box, and these lists grow — units and HSN codes in
 * particular. Any <select data-searchable> gets upgraded.
 */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('select[data-searchable]').forEach((el) => {
        const settings = {
            allowEmptyOption: true,
            maxOptions: null,
            placeholder: el.dataset.placeholder || 'Search…',
        };

        if (el.multiple) {
            settings.plugins = ['remove_button'];
        }

        new TomSelect(el, settings);
    });
});

/**
 * Password visibility toggle.
 *
 * Delegated from the document so it works for password fields rendered after
 * load (modals, the user edit form) without re-binding. A button may name its
 * field with data-target="#id"; otherwise it toggles the field inside its own
 * input group.
 */
document.addEventListener('click', (event) => {
    const button = event.target.closest('.toggle-password');
    if (!button) return;

    const group = button.closest('.input-group') || button.parentElement;
    const input = button.dataset.target
        ? document.querySelector(button.dataset.target)
        : group?.querySelector('input[type="password"], input[type="text"]');

    if (!input) return;

    const showing = input.type === 'password';
    input.type = showing ? 'text' : 'password';

    // Outline eye while masked, solid eye while visible. No slashed variant —
    // a struck-through eye reads as "disabled" more than "hidden".
    const icon = button.querySelector('i');
    if (icon) {
        icon.classList.toggle('bi-eye', !showing);
        icon.classList.toggle('bi-eye-fill', showing);
    }

    button.setAttribute('aria-pressed', showing ? 'true' : 'false');
    button.setAttribute('aria-label', showing ? 'Hide password' : 'Show password');

    // Keep the caret where the user left it — switching `type` sends it to the
    // end in Chrome, which is jarring mid-edit.
    if (document.activeElement === input) {
        const end = input.value.length;
        input.setSelectionRange(end, end);
    }
});
