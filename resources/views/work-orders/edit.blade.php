<x-app-layout>
    <x-slot name="header">Edit {{ $workOrder->wo_num }}</x-slot>

    <x-ui.card title="Edit Work Order {{ $workOrder->wo_num }}" variant="primary">
        <form action="{{ route('work-orders.update', $workOrder) }}" method="POST">
            @csrf
            @method('PUT')
            @include('work-orders._form', ['workOrder' => $workOrder])
            <div class="form-actions">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check-lg me-1"></i> Update
                </button>
                <a href="{{ route('work-orders.show', $workOrder) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </x-ui.card>
</x-app-layout>
