<x-app-layout>
    <x-slot name="header">New Order Confirmation</x-slot>

    <x-ui.card title="New Order Confirmation" variant="primary">
        <x-slot name="actions">
            <a href="{{ route('sales.order-confirmations.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </x-slot>

        <form action="{{ route('sales.order-confirmations.store') }}" method="POST" id="oc-form">
            @csrf
            @include('sales.order-confirmations._form', ['orderConfirmation' => null])
        </form>
    </x-ui.card>
</x-app-layout>
