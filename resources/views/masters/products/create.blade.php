<x-app-layout>
    <x-slot name="header">Add Product</x-slot>

    <x-ui.card title="New Product" variant="primary">
        <form action="{{ route('masters.products.store') }}" method="POST">
            @csrf
            @include('masters.products._form', ['product' => null])
        </form>
    </x-ui.card>
</x-app-layout>
