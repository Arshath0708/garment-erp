<x-app-layout>
    <x-slot name="header">Edit Order Format</x-slot>

    <x-ui.card title="{{ $format->name }}" variant="primary">
        <form action="{{ route('masters.formats.update', $format) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('masters.formats._form')
        </form>
    </x-ui.card>
</x-app-layout>
