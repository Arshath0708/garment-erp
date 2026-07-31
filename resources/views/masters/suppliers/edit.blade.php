<x-app-layout>
    <x-slot name="header">Edit Supplier</x-slot>

    <x-ui.card title="{{ $supplier->display_code }} — {{ $supplier->company_name }}" variant="primary">
        <form action="{{ route('masters.suppliers.update', $supplier) }}" method="POST">
            @csrf
            @method('PUT')
            @include('masters.suppliers._form')
        </form>
    </x-ui.card>
</x-app-layout>
