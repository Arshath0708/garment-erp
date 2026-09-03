<x-app-layout>
    <x-slot name="header">New Style Costing</x-slot>

    <x-ui.card title="Create Style Costing" variant="primary">
        <form action="{{ route('style-costings.store') }}" method="POST">
            @csrf
            @include('style-costings._form')
            <div class="form-actions">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check-lg me-1"></i> Save Draft
                </button>
                <a href="{{ route('style-costings.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </x-ui.card>
</x-app-layout>
