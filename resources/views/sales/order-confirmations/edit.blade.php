<x-app-layout>
    <x-slot name="header">Edit Order Confirmation</x-slot>

    <x-ui.card title="{{ $orderConfirmation->oc_num }}" variant="primary">
        <x-slot name="actions">
            <a href="{{ route('sales.order-confirmations.show', $orderConfirmation) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-eye me-1"></i> View
            </a>
        </x-slot>

        <form action="{{ route('sales.order-confirmations.update', $orderConfirmation) }}" method="POST" id="oc-form">
            @csrf
            @method('PUT')
            @include('sales.order-confirmations._form')
        </form>
    </x-ui.card>
</x-app-layout>
