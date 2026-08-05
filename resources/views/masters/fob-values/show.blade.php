<x-app-layout>
    <x-slot name="header">FOB Value Details</x-slot>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <x-ui.card title="FOB Value: {{ $fobValue->name }}" variant="primary">
                <x-slot name="actions">
                    @can('fob-value.edit')
                        <a href="{{ route('masters.fob-values.edit', $fobValue) }}" class="btn btn-sm btn-primary">
                            <i class="bi bi-pencil me-1"></i> Edit
                        </a>
                    @endcan
                    <a href="{{ route('masters.fob-values.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back to List
                    </a>
                </x-slot>

                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <tbody>
                            <tr>
                                <th style="width:200px" class="bg-light">FOB Value Name</th>
                                <td>{{ $fobValue->name }}</td>
                            </tr>
                            <tr>
                                <th class="bg-light">Status</th>
                                <td><x-ui.status-badge :status="$fobValue->status === 'active'" /></td>
                            </tr>
                            <tr>
                                <th class="bg-light">Remarks</th>
                                <td>{{ $fobValue->remarks ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th class="bg-light">Created By</th>
                                <td>{{ $fobValue->creator?->name ?? '—' }} ({{ $fobValue->created_at?->format('d M Y H:i') ?? '—' }})</td>
                            </tr>
                            <tr>
                                <th class="bg-light">Updated By</th>
                                <td>{{ $fobValue->updater?->name ?? '—' }} ({{ $fobValue->updated_at?->format('d M Y H:i') ?? '—' }})</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
