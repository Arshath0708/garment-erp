<x-app-layout>
    <x-slot name="header">Edit Category</x-slot>

    <x-ui.card title="{{ $category->code }} — {{ $category->name }}" variant="primary">
        <form action="{{ route('masters.categories.update', $category) }}" method="POST">
            @csrf
            @method('PUT')
            @include('masters.categories._form', ['nextCode' => null])
        </form>
    </x-ui.card>
</x-app-layout>
