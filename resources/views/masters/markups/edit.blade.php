<x-app-layout>
    <x-slot name="header">Edit Markup Rule</x-slot>

    <x-ui.card title="{{ $markup->supplier->company_name }} → {{ $markup->buyer->company_name }}" variant="primary">
        <form action="{{ route('masters.markups.update', $markup) }}" method="POST">
            @csrf
            @method('PUT')
            @include('masters.markups._form')
        </form>
    </x-ui.card>
</x-app-layout>
