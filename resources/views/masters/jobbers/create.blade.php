<x-app-layout>
    <x-slot name="header">Add Jobber</x-slot>

    <x-ui.card title="New Jobber" variant="primary">
        <form action="{{ route('masters.jobbers.store') }}" method="POST">
            @csrf
            @include('masters.jobbers._form', ['supplier' => null])
        </form>
    </x-ui.card>
</x-app-layout>
