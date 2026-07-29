<x-app-layout>
    <x-slot name="header">Add User</x-slot>

    <div class="row">
        <div class="col-lg-10 col-xl-9">
            <x-ui.card title="New User" variant="primary">
                <form action="{{ route('user-management.users.store') }}" method="POST">
                    @csrf
                    @include('user-management.users._form', ['userRoles' => []])
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
