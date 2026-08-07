<x-app-layout>
    <x-slot name="header">New Inquiry</x-slot>

    <x-ui.card title="New Inquiry" variant="primary">
        <x-slot name="actions">
            <a href="{{ route('sales.inquiries.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </x-slot>

        <form action="{{ route('sales.inquiries.store') }}" method="POST" id="inquiry-form">
            @csrf
            @include('sales.inquiries._form', ['inquiry' => null])
        </form>
    </x-ui.card>
</x-app-layout>
