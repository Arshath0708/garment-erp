<x-app-layout>
    <x-slot name="header">Add Markup Rule</x-slot>

    <x-ui.card title="New Markup Rule" variant="primary">
        <form action="{{ route('masters.markups.store') }}" method="POST">
            @csrf
            @include('masters.markups._form', ['markup' => null])
        </form>
    </x-ui.card>
</x-app-layout>
