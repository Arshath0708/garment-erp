@props(['status' => false, 'activeLabel' => 'Active', 'inactiveLabel' => 'Inactive'])

<span class="badge rounded-pill {{ $status ? 'text-bg-success' : 'text-bg-secondary' }}">
    <i class="bi {{ $status ? 'bi-check-circle-fill' : 'bi-slash-circle' }} me-1"></i>{{ $status ? $activeLabel : $inactiveLabel }}
</span>
