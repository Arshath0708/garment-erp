<x-app-layout>
    <x-slot name="header">Edit {{ $costing->costing_num }}</x-slot>

    <x-ui.card title="Edit Costing {{ $costing->costing_num }}" variant="primary">
        <form action="{{ route('style-costings.update', $costing) }}" method="POST">
            @csrf
            @method('PUT')
            @include('style-costings._form', ['costing' => $costing, 'lines' => $lines])
            <div class="form-actions">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check-lg me-1"></i> Update
                </button>
                <a href="{{ route('style-costings.show', $costing) }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </x-ui.card>
</x-app-layout>
