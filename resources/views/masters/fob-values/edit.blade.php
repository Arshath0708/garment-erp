<x-app-layout>
    <x-slot name="header">Edit FOB Value</x-slot>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <x-ui.card title="Edit FOB Value: {{ $fobValue->name }}" variant="primary">
                <form action="{{ route('masters.fob-values.update', $fobValue) }}" method="POST">
                    @csrf
                    @method('PUT')
                    @include('masters.fob-values._form')

                    <div class="mt-4 d-flex gap-2 justify-content-end">
                        <a href="{{ route('masters.fob-values.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i> Update FOB Value
                        </button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
