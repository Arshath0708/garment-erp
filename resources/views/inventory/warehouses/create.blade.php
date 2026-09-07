<x-app-layout>
    <x-slot name="header">Add godown</x-slot>

    <div class="card shadow-sm border-0" style="max-width: 560px">
        <div class="card-body">
            <form method="POST" action="{{ route('inventory.warehouses.store') }}" class="vstack gap-3">
                @csrf
                @include('inventory.warehouses._form')
                <div class="d-flex gap-2">
                    <button class="btn btn-primary">Save</button>
                    <a href="{{ route('inventory.warehouses.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
