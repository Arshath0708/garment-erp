<x-app-layout>
    <x-slot name="header">Add Supplier</x-slot>

    <x-ui.card title="New Supplier" variant="primary">
        <form action="{{ route('masters.suppliers.store') }}" method="POST">
            @csrf
            @include('masters.suppliers._form', ['supplier' => null])
        </form>
    </x-ui.card>
</x-app-layout>
