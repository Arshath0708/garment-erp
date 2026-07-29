<x-app-layout>
    <x-slot name="header">Edit User</x-slot>

    <div class="row">
        <div class="col-lg-10 col-xl-9">
            <x-ui.card :title="$user->name" variant="primary">
                <x-slot name="actions">
                    <a href="{{ route('user-management.users.show', $user) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-eye me-1"></i>View
                    </a>
                </x-slot>

                <form action="{{ route('user-management.users.update', $user) }}" method="POST">
                    @csrf
                    @method('PUT')
                    @include('user-management.users._form')
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
