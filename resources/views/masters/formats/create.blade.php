<x-app-layout>
    <x-slot name="header">Add Order Format</x-slot>

    <x-ui.card title="New Order Format" variant="primary">
        {{-- enctype: the section carries reference-image uploads. --}}
        <form action="{{ route('masters.formats.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @include('masters.formats._form', ['format' => null])
        </form>
    </x-ui.card>
</x-app-layout>
