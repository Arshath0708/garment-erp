<x-app-layout>
    <x-slot name="header">Role Details</x-slot>

    <x-ui.card :title="$role->name" variant="primary" class="mb-3">
        <x-slot name="actions">
            @can('role.edit')
                <a href="{{ route('user-management.roles.edit', $role) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-pencil me-1"></i>Edit
                </a>
            @endcan
            <a href="{{ route('user-management.roles.index') }}" class="btn btn-sm btn-outline-secondary">Back</a>
        </x-slot>

        <div class="row g-3">
            <div class="col-md-8">
                <p class="text-body-secondary mb-2">{{ $registry->roleDescription($role->name) ?? 'No description.' }}</p>
                <div class="d-flex gap-2 flex-wrap">
                    <span class="badge text-bg-light border">
                        {{ $role->name === 'Super Admin' ? 'All' : $role->permissions->count() }} permissions
                    </span>
                    <span class="badge text-bg-light border">{{ $role->users->count() }} users</span>
                    @if($registry->isSystemRole($role->name))
                        <span class="badge text-bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                            <i class="bi bi-shield-lock-fill me-1"></i>System role
                        </span>
                    @endif
                </div>
            </div>

            <div class="col-md-4">
                <div class="fw-semibold small text-uppercase text-body-secondary mb-1">Assigned users</div>
                @forelse($role->users->take(10) as $user)
                    <span class="badge text-bg-light border">{{ $user->name }}</span>
                @empty
                    <span class="text-body-secondary small">None</span>
                @endforelse
                @if($role->users->count() > 10)
                    <span class="badge text-bg-light border">+{{ $role->users->count() - 10 }} more</span>
                @endif
            </div>
        </div>
    </x-ui.card>

    <x-ui.card title="Permissions" variant="secondary">
        @include('user-management.roles._matrix', [
            'groups'          => $groups,
            'actions'         => $actions,
            'rolePermissions' => $rolePermissions,
            'readonly'        => true,
        ])
    </x-ui.card>
</x-app-layout>
