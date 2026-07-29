import './bootstrap';

import 'bootstrap';
import 'admin-lte/dist/js/adminlte.js';import ApexCharts from 'apexcharts'; window.ApexCharts = ApexCharts;

// Password visibility toggle
document.addEventListener('click', function (event) {
    const button = event.target.closest('.toggle-password');
    if (!button) return;

    const container = button.closest('.input-group') || button.parentElement;
    if (!container) return;

    const input = container.querySelector('input');
    if (!input) return;

    const icon = button.querySelector('i');

    if (input.type === 'password') {
        input.type = 'text';
        if (icon) {
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        }
    } else {
        input.type = 'password';
        if (icon) {
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }
});

