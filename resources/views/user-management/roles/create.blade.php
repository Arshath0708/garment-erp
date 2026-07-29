<x-app-layout>
    <x-slot name="header">Add Role</x-slot>

    <form action="{{ route('user-management.roles.store') }}" method="POST">
        @csrf

        <x-ui.card title="New Role" variant="primary" class="mb-3">
            <div class="row">
                <x-ui.field name="name" label="Role Name" required col="col-md-6"
                            placeholder="e.g. Store Keeper"
                            hint="Choose a name that describes the job, not the person." />
            </div>
        </x-ui.card>

        <x-ui.card title="Permissions" variant="secondary">
            @include('user-management.roles._matrix', [
                'groups'          => $groups,
                'actions'         => $actions,
                'rolePermissions' => $rolePermissions,
                'readonly'        => false,
            ])

            <x-slot name="footer">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Create Role
                    </button>
                    <a href="{{ route('user-management.roles.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </x-slot>
        </x-ui.card>
    </form>
</x-app-layout>
