<x-app-layout>
    <x-slot name="header">User Details</x-slot>

    <div class="row g-3">
        <div class="col-lg-5">
            <x-ui.card :title="$user->name" variant="primary">
                <x-slot name="actions">
                    @can('user.edit')
                        <a href="{{ route('user-management.users.edit', $user) }}" class="btn btn-sm btn-primary">
                            <i class="bi bi-pencil me-1"></i>Edit
                        </a>
                    @endcan
                </x-slot>

                <dl class="row mb-0 small">
                    <dt class="col-5 text-body-secondary fw-normal">Email</dt>
                    <dd class="col-7">{{ $user->email }}</dd>

                    <dt class="col-5 text-body-secondary fw-normal">Phone</dt>
                    <dd class="col-7">{{ $user->phone ?: '—' }}</dd>

                    <dt class="col-5 text-body-secondary fw-normal">Status</dt>
                    <dd class="col-7"><x-ui.status-badge :status="$user->status" /></dd>

                    <dt class="col-5 text-body-secondary fw-normal">Created by</dt>
                    <dd class="col-7">{{ $user->creator?->name ?? 'System' }}</dd>

                    <dt class="col-5 text-body-secondary fw-normal">Created at</dt>
                    <dd class="col-7">{{ $user->created_at?->format('d M Y, h:i A') }}</dd>

                    <dt class="col-5 text-body-secondary fw-normal">Roles</dt>
                    <dd class="col-7">
                        @foreach($user->roles as $role)
                            <span class="badge text-bg-info-subtle text-info-emphasis border border-info-subtle">{{ $role->name }}</span>
                        @endforeach
                    </dd>
                </dl>
            </x-ui.card>
        </div>

        <div class="col-lg-7">
            <x-ui.card title="Effective Permissions" variant="secondary">
                <x-slot name="actions">
                    <span class="badge text-bg-light">
                        {{ $user->isSuperAdmin() ? 'All' : $user->getAllPermissions()->count() }}
                    </span>
                </x-slot>

                @if($user->isSuperAdmin())
                    <div class="alert alert-warning py-2 mb-0 small">
                        <i class="bi bi-shield-lock-fill me-1"></i>
                        <strong>Super Admin</strong> bypasses every permission check.
                    </div>
                @else
                    @php
                        $grouped = app(\App\Support\PermissionRegistry::class)
                            ->groupExisting($user->getAllPermissions());
                    @endphp

                    @forelse($grouped as $group => $permissions)
                        <div class="mb-3">
                            <div class="fw-semibold small text-uppercase text-body-secondary mb-1">{{ $group }}</div>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($permissions as $permission)
                                    <span class="badge text-bg-light border font-monospace fw-normal">{{ $permission->name }}</span>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <x-ui.empty-state icon="bi-shield-slash" title="No permissions"
                                          message="This user's roles grant no permissions." />
                    @endforelse
                @endif
            </x-ui.card>
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('user-management.users.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Users
        </a>
    </div>
</x-app-layout>
