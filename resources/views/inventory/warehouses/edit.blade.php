<x-app-layout>
    <x-slot name="header">Edit godown</x-slot>

    <div class="card shadow-sm border-0" style="max-width: 560px">
        <div class="card-body">
            <form method="POST" action="{{ route('inventory.warehouses.update', $warehouse) }}" class="vstack gap-3">
                @csrf
                @method('PUT')
                @include('inventory.warehouses._form')
                <div class="d-flex gap-2">
                    <button class="btn btn-primary">Update</button>
                    <a href="{{ route('inventory.warehouses.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
