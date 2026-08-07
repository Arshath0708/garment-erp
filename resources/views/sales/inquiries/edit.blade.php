<x-app-layout>
    <x-slot name="header">Edit Inquiry</x-slot>

    <x-ui.card title="{{ $inquiry->inquiry_no }}" variant="primary">
        <x-slot name="actions">
            <a href="{{ route('sales.inquiries.show', $inquiry) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-eye me-1"></i> View
            </a>
        </x-slot>

        <form action="{{ route('sales.inquiries.update', $inquiry) }}" method="POST" id="inquiry-form">
            @csrf
            @method('PUT')
            @include('sales.inquiries._form')
        </form>
    </x-ui.card>
</x-app-layout>
