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

        // Multi-select (Buyer sheet col D, "allow multiple selection") needs
        // a way to take one back off without reopening the list.
        if (el.multiple) {
            settings.plugins = ['remove_button'];
        }

        // "Drop down, add more in the future" (Buyer sheet col Q, Payment
        // Terms): typing a name not already in the list posts it to
        // data-create-url and adds the row it comes back with.
        if (el.dataset.createUrl) {
            settings.create = function (input, callback) {
                const token = document.querySelector('meta[name="csrf-token"]')?.content;

                fetch(el.dataset.createUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': token || '',
                    },
                    body: JSON.stringify({ name: input }),
                })
                    .then((r) => (r.ok ? r.json() : Promise.reject()))
                    .then((row) => callback({ value: String(row.id), text: row.name }))
                    // A failed create must not leave TomSelect thinking one
                    // happened — no option is added if the request failed.
                    .catch(() => callback());
            };
            settings.createOnBlur = true;
        }

        new TomSelect(el, settings);
    });

    initCascadingSelects();
});

/**
 * Dependent dropdowns — Country -> State -> City on the Buyer form.
 *
 * Declared in markup rather than wired per-screen, so the Supplier, Jobber and
 * Agent forms get the same behaviour by adding the same attributes:
 *
 *   data-cascade-parent  selector for the select this one depends on
 *   data-cascade-url     endpoint returning [{id, name}, ...]
 *   data-cascade-key     query parameter to send the parent's value as
 *   data-cascade-child   selector for the select that depends on THIS one
 *   data-cascade-empty   placeholder shown while the parent has no value
 *
 * The initial options are rendered server-side, so an edit form is already
 * correct and nothing is fetched until the user actually changes a parent.
 * Chains of any length work: each level clears the one below it.
 */
function initCascadingSelects() {
    const children = document.querySelectorAll('select[data-cascade-parent]');
    if (! children.length) return;

    children.forEach((child) => {
        const parent = document.querySelector(child.dataset.cascadeParent);
        if (! parent) return;

        // A child whose parent is empty starts disabled — an open dropdown
        // listing every city in the world is worse than one that says why it
        // is empty.
        applyEnabledState(child, parent.value);

        parent.addEventListener('change', () => reload(child, parent.value));
    });

    function control(select) {
        // TomSelect hangs its instance off the element it replaced.
        return select.tomselect || null;
    }

    function applyEnabledState(select, parentValue) {
        const ts = control(select);
        const enabled = Boolean(parentValue);
        const placeholder = enabled
            ? (select.dataset.placeholder || 'Search…')
            : (select.dataset.cascadeEmpty || 'Select the field above first');

        select.disabled = ! enabled;

        if (ts) {
            enabled ? ts.enable() : ts.disable();
            ts.settings.placeholder = placeholder;
            ts.control_input.placeholder = placeholder;
        }
    }

    /**
     * Repopulate one select from its endpoint, then cascade the reset downward.
     *
     * The child is cleared before the request rather than after it: leaving a
     * stale city visible while its new list loads is how a buyer ends up saved
     * in a city that is not in the country on screen.
     */
    function reload(select, parentValue) {
        const ts = control(select);

        if (ts) { ts.clear(true); ts.clearOptions(); } else { select.innerHTML = ''; }

        applyEnabledState(select, parentValue);
        resetDescendants(select);

        if (! parentValue) return;

        const url = new URL(select.dataset.cascadeUrl, window.location.origin);
        url.searchParams.set(select.dataset.cascadeKey, parentValue);

        if (ts) ts.load(() => {});

        fetch(url, { headers: { Accept: 'application/json' } })
            .then((r) => (r.ok ? r.json() : Promise.reject(r.status)))
            .then((rows) => {
                if (ts) {
                    ts.addOptions(rows.map((row) => ({ value: String(row.id), text: row.name })));
                    ts.refreshOptions(false);
                } else {
                    select.append(new Option(select.dataset.placeholder || '— Select —', ''));
                    rows.forEach((row) => select.append(new Option(row.name, row.id)));
                }
            })
            // A failed lookup must not leave the field looking loaded-but-empty,
            // which reads as "this country has no states".
            .catch(() => applyEnabledState(select, ''));
    }

    /** Clearing a select invalidates everything hanging off it. */
    function resetDescendants(select) {
        if (! select.dataset.cascadeChild) return;

        const child = document.querySelector(select.dataset.cascadeChild);
        if (child) reload(child, '');
    }
}

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
