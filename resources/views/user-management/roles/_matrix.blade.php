{{--
    Permission matrix.

    Rows are modules, columns are actions. Every checkbox is derived from
    config/permissions.php, so the UI can never offer a permission that does
    not exist in code.

    $groups          array<string, array<string, array{label,actions,built}>>
    $actions         array<int, string>   column order
    $rolePermissions array<int, string>   currently granted
    $readonly        bool                 render disabled (show / Super Admin)
--}}
@props(['groups', 'actions', 'rolePermissions' => [], 'readonly' => false])

@php $registry = app(\App\Support\PermissionRegistry::class); @endphp

<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
    @unless($readonly)
        <button type="button" class="btn btn-sm btn-outline-primary" data-matrix-all="1">
            <i class="bi bi-check-all me-1"></i>Select all
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-matrix-all="0">
            <i class="bi bi-x-lg me-1"></i>Clear all
        </button>
        <span class="vr d-none d-md-block"></span>
        @foreach($actions as $action)
            <button type="button" class="btn btn-sm btn-outline-secondary" data-matrix-action="{{ $action }}">
                All {{ strtolower($registry->actionLabel($action)) }}
            </button>
        @endforeach
    @endunless
    <span class="ms-auto badge text-bg-light border">
        <span data-matrix-count>{{ count($rolePermissions) }}</span> selected
    </span>
</div>

@error('permissions')<div class="alert alert-danger py-2 small">{{ $message }}</div>@enderror

<div class="accordion" id="permissionMatrix">
    @foreach($groups as $groupName => $modules)
        @php
            $groupSlug = \Illuminate\Support\Str::slug($groupName);
            $groupPermissions = collect($modules)
                ->flatMap(fn ($meta, $module) => collect($meta['actions'])->map(fn ($a) => "{$module}.{$a}"))
                ->all();
            $grantedInGroup = count(array_intersect($groupPermissions, $rolePermissions));
        @endphp

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#grp-{{ $groupSlug }}">
                    <span class="fw-semibold">{{ $groupName }}</span>
                    <span class="badge text-bg-light border ms-2" data-group-count="{{ $groupSlug }}">
                        {{ $grantedInGroup }}/{{ count($groupPermissions) }}
                    </span>
                </button>
            </h2>

            <div id="grp-{{ $groupSlug }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}"
                 data-bs-parent="#permissionMatrix">
                <div class="accordion-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0 matrix-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="min-width:220px">Module</th>
                                    @foreach($actions as $action)
                                        <th class="text-center" style="width:90px">{{ $registry->actionLabel($action) }}</th>
                                    @endforeach
                                    @unless($readonly)
                                        <th class="text-center" style="width:70px">All</th>
                                    @endunless
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($modules as $module => $meta)
                                    <tr data-module-row="{{ $module }}">
                                        <td>
                                            <span class="fw-medium">{{ $meta['label'] }}</span>
                                            @unless($meta['built'])
                                                <span class="badge text-bg-light border text-body-secondary ms-1"
                                                      data-bs-toggle="tooltip"
                                                      title="Screen not built yet — the permission can still be assigned now">
                                                    planned
                                                </span>
                                            @endunless
                                            <div class="small text-body-secondary font-monospace">{{ $module }}.*</div>
                                        </td>

                                        @foreach($actions as $action)
                                            <td class="text-center">
                                                @if(in_array($action, $meta['actions'], true))
                                                    @php $name = "{$module}.{$action}"; @endphp
                                                    <input class="form-check-input matrix-check"
                                                           type="checkbox"
                                                           name="permissions[]"
                                                           value="{{ $name }}"
                                                           id="perm-{{ $name }}"
                                                           data-group="{{ $groupSlug }}"
                                                           data-module="{{ $module }}"
                                                           data-action="{{ $action }}"
                                                           @checked(in_array($name, old('permissions', $rolePermissions), true))
                                                           @disabled($readonly)>
                                                @else
                                                    <span class="text-body-secondary opacity-25">—</span>
                                                @endif
                                            </td>
                                        @endforeach

                                        @unless($readonly)
                                            <td class="text-center">
                                                <input class="form-check-input" type="checkbox"
                                                       data-module-all="{{ $module }}"
                                                       data-bs-toggle="tooltip" title="Toggle whole row">
                                            </td>
                                        @endunless
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

@once
    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const root = document.getElementById('permissionMatrix');
            if (!root) return;

            const checks = () => Array.from(root.querySelectorAll('.matrix-check:not(:disabled)'));
            const countEl = document.querySelector('[data-matrix-count]');

            function refresh() {
                if (countEl) {
                    countEl.textContent = root.querySelectorAll('.matrix-check:checked').length;
                }

                // Per-group counter
                document.querySelectorAll('[data-group-count]').forEach(function (badge) {
                    const slug = badge.dataset.groupCount;
                    const all = root.querySelectorAll('.matrix-check[data-group="' + slug + '"]');
                    const on = root.querySelectorAll('.matrix-check[data-group="' + slug + '"]:checked');
                    badge.textContent = on.length + '/' + all.length;
                });

                // Row "All" checkbox reflects its row
                root.querySelectorAll('[data-module-all]').forEach(function (box) {
                    const module = box.dataset.moduleAll;
                    const all = root.querySelectorAll('.matrix-check[data-module="' + module + '"]');
                    const on = root.querySelectorAll('.matrix-check[data-module="' + module + '"]:checked');
                    box.checked = all.length > 0 && on.length === all.length;
                    box.indeterminate = on.length > 0 && on.length < all.length;
                });
            }

            root.addEventListener('change', function (e) {
                if (e.target.matches('[data-module-all]')) {
                    const module = e.target.dataset.moduleAll;
                    root.querySelectorAll('.matrix-check[data-module="' + module + '"]:not(:disabled)')
                        .forEach(function (c) { c.checked = e.target.checked; });
                }
                refresh();
            });

            document.querySelectorAll('[data-matrix-all]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const on = btn.dataset.matrixAll === '1';
                    checks().forEach(function (c) { c.checked = on; });
                    refresh();
                });
            });

            document.querySelectorAll('[data-matrix-action]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const action = btn.dataset.matrixAction;
                    const rows = root.querySelectorAll('.matrix-check[data-action="' + action + '"]:not(:disabled)');
                    // Toggle: if all are already on, turn them off.
                    const allOn = Array.from(rows).every(function (c) { return c.checked; });
                    rows.forEach(function (c) { c.checked = !allOn; });
                    refresh();
                });
            });

            refresh();
        });
        </script>
    @endpush
@endonce
