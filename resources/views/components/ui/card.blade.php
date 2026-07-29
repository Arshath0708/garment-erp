@props([
    'title' => null,
    'variant' => 'primary',
    'actions' => null,
    'bodyClass' => '',
])

<div {{ $attributes->merge(['class' => "card card-outline card-{$variant} shadow-sm"]) }}>
    @if($title || $actions)
        <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
            @if($title)
                <h3 class="card-title mb-0">{{ $title }}</h3>
            @endif

            @if($actions)
                <div class="d-flex flex-wrap gap-2 ms-auto">{{ $actions }}</div>
            @endif
        </div>
    @endif

    <div class="card-body {{ $bodyClass }}">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="card-footer">{{ $footer }}</div>
    @endisset
</div>
