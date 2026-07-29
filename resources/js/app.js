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
        new TomSelect(el, {
            allowEmptyOption: true,
            maxOptions: null,
            placeholder: el.dataset.placeholder || 'Search…',
        });
    });
});
