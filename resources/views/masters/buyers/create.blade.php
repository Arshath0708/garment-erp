<x-app-layout>
    <x-slot name="header">Add Buyer</x-slot>

    <x-ui.card title="New Buyer" variant="primary">
        <form action="{{ route('masters.buyers.store') }}" method="POST">
            @csrf
            @include('masters.buyers._form', ['buyer' => null])
        </form>
    </x-ui.card>
</x-app-layout>
