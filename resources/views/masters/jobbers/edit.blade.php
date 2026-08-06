<x-app-layout>
    <x-slot name="header">Edit Jobber</x-slot>

    <x-ui.card title="Edit Jobber: {{ $jobber->company_name }}" variant="primary">
        <form action="{{ route('masters.jobbers.update', $jobber) }}" method="POST">
            @csrf
            @method('PUT')
            @include('masters.jobbers._form', ['supplier' => $jobber])
        </form>
    </x-ui.card>
</x-app-layout>
