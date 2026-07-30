<x-app-layout>
    <x-slot name="header">Add Agent</x-slot>

    <x-ui.card title="Create Agent Master" variant="primary">
        <form action="{{ route('masters.agents.store') }}" method="POST">
            @csrf
            @include('masters.agents._form')
        </form>
    </x-ui.card>
</x-app-layout>
