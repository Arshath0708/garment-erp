<x-app-layout>
    <x-slot name="header">Add Category</x-slot>

    <x-ui.card title="New Category" variant="primary">
        <form action="{{ route('masters.categories.store') }}" method="POST">
            @csrf
            @include('masters.categories._form', ['category' => null])
        </form>
    </x-ui.card>
</x-app-layout>
