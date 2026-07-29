@props([
    'action',
    'label' => 'Delete',
    'confirm' => 'Are you sure? This cannot be undone.',
    'disabled' => false,
    'disabledReason' => null,
    'icon' => 'bi-trash',
])

@if($disabled)
    <button type="button" class="btn btn-sm btn-outline-danger" disabled
            data-bs-toggle="tooltip" title="{{ $disabledReason }}">
        <i class="bi {{ $icon }}"></i>
    </button>
@else
    <form action="{{ $action }}" method="POST" class="d-inline js-confirm" data-confirm="{{ $confirm }}">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="{{ $label }}">
            <i class="bi {{ $icon }}"></i>
        </button>
    </form>
@endif
