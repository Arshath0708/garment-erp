<x-app-layout>
    <x-slot name="header">New Work Order</x-slot>

    <x-ui.card title="Create Work Order" variant="primary">
        <form action="{{ route('work-orders.store') }}" method="POST">
            @csrf
            @include('work-orders._form')
            <div class="form-actions">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check-lg me-1"></i> Save Draft
                </button>
                <a href="{{ route('work-orders.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </x-ui.card>
</x-app-layout>
