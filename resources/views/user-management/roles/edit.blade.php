<x-app-layout>
    <x-slot name="header">Edit Role</x-slot>

    <form action="{{ route('user-management.roles.update', $role) }}" method="POST">
        @csrf
        @method('PUT')

        <x-ui.card :title="$role->name" variant="primary" class="mb-3">
            @if($isLocked)
                <div class="alert alert-warning py-2 small mb-3">
                    <i class="bi bi-shield-lock-fill me-1"></i>
                    <strong>Super Admin</strong> bypasses every permission check through a gate, so its
                    permission list is fixed and shown read-only.
                </div>
            @elseif($isSystemRole)
                <div class="alert alert-info py-2 small mb-3">
                    <i class="bi bi-info-circle me-1"></i>
                    This is a <strong>system role</strong>. Its name is fixed because route middleware
                    refers to it, but you can change what it is allowed to do.
                </div>
            @endif

            <div class="row">
                <x-ui.field name="name" label="Role Name" :value="$role->name" required col="col-md-6"
                            :readonly="$isSystemRole"
                            :hint="$isSystemRole ? 'System role names cannot be changed.' : null" />

                @if($registry->roleDescription($role->name))
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <p class="form-control-plaintext text-body-secondary small mb-0">
                            {{ $registry->roleDescription($role->name) }}
                        </p>
                    </div>
                @endif
            </div>
        </x-ui.card>

        <x-ui.card title="Permissions" variant="secondary">
            @include('user-management.roles._matrix', [
                'groups'          => $groups,
                'actions'         => $actions,
                'rolePermissions' => $rolePermissions,
                'readonly'        => $isLocked,
            ])

            <x-slot name="footer">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Update Role
                    </button>
                    <a href="{{ route('user-management.roles.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </x-slot>
        </x-ui.card>
    </form>
</x-app-layout>
