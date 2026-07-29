<x-app-layout>
    <x-slot name="header">Permissions</x-slot>

    <div class="alert alert-light border small">
        <i class="bi bi-info-circle me-1"></i>
        Permissions are declared in <code>config/permissions.php</code> and are read-only here.
        A permission created by hand would not match any <code>&#64;can()</code> check in the code — it
        would grant nothing and fail silently. To add one, edit the config file and press
        <strong>Sync</strong>.
    </div>

    @if($diff['missing'] || $diff['orphaned'])
        <div class="alert alert-warning">
            <div class="fw-semibold mb-2"><i class="bi bi-exclamation-triangle me-1"></i>Config and database are out of sync</div>

            @if($diff['missing'])
                <div class="small mb-1">
                    <strong>{{ count($diff['missing']) }} declared in config but missing from the database:</strong>
                    <div class="d-flex flex-wrap gap-1 mt-1">
                        @foreach($diff['missing'] as $name)
                            <span class="badge text-bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace fw-normal">{{ $name }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($diff['orphaned'])
                <div class="small mt-2">
                    <strong>{{ count($diff['orphaned']) }} in the database but no longer declared:</strong>
                    <div class="d-flex flex-wrap gap-1 mt-1">
                        @foreach($diff['orphaned'] as $name)
                            <span class="badge text-bg-danger-subtle text-danger-emphasis border border-danger-subtle font-monospace fw-normal">{{ $name }}</span>
                        @endforeach
                    </div>
                    <div class="mt-1 text-body-secondary">
                        Sync will not delete these — removing a permission also strips it from every role.
                        Run <code>php artisan permission:sync --prune</code> to remove them deliberately.
                    </div>
                </div>
            @endif
        </div>
    @endif

    <x-ui.card title="Registered Permissions" variant="primary">
        <x-slot name="actions">
            <span class="badge text-bg-light border align-self-center">{{ $total }} total</span>
            @can('permission.sync')
                <form action="{{ route('user-management.permissions.sync') }}" method="POST"
                      class="js-confirm d-inline" data-confirm="Re-read config/permissions.php and create any missing permissions?">
                    @csrf
                    <button class="btn btn-sm btn-primary">
                        <i class="bi bi-arrow-repeat me-1"></i>Sync from Config
                    </button>
                </form>
            @endcan
        </x-slot>

        <div class="accordion" id="permissionList">
            @foreach($grouped as $group => $permissions)
                @php $slug = \Illuminate\Support\Str::slug($group); @endphp
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button"
                                data-bs-toggle="collapse" data-bs-target="#perm-grp-{{ $slug }}">
                            <span class="fw-semibold">{{ $group }}</span>
                            <span class="badge text-bg-light border ms-2">{{ $permissions->count() }}</span>
                            @if($group === 'Unregistered')
                                <span class="badge text-bg-danger-subtle text-danger-emphasis border border-danger-subtle ms-2">
                                    not in config
                                </span>
                            @endif
                        </button>
                    </h2>
                    <div id="perm-grp-{{ $slug }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}"
                         data-bs-parent="#permissionList">
                        <div class="accordion-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Permission</th>
                                            <th style="width:180px">Module</th>
                                            <th style="width:140px">Action</th>
                                            <th class="text-center" style="width:110px">Used by roles</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($permissions as $permission)
                                            <tr>
                                                <td class="font-monospace small">{{ $permission->name }}</td>
                                                <td class="text-body-secondary small">{{ \Illuminate\Support\Str::beforeLast($permission->name, '.') }}</td>
                                                <td class="text-body-secondary small">{{ $registry->actionLabel(\Illuminate\Support\Str::afterLast($permission->name, '.')) }}</td>
                                                <td class="text-center">
                                                    <span class="badge {{ $permission->roles_count > 0 ? 'text-bg-light border' : 'text-bg-light border text-body-secondary opacity-50' }}">
                                                        {{ $permission->roles_count }}
                                                    </span>
                                                </td>
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
    </x-ui.card>
</x-app-layout>
