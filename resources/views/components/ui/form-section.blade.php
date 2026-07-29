@props([
    'title',
    'icon' => null,
    'subtitle' => null,
])

{{--
    One labelled block of a long form.

    A 20-field product form as a single flat grid is unreadable — the user has
    to remember which fields belong together. Grouping them under a heading with
    an icon means the form is scanned rather than read.
--}}

<section {{ $attributes->merge(['class' => 'form-section']) }}>
    <header class="form-section-head">
        @if($icon)
            <span class="form-section-icon"><i class="bi {{ $icon }}"></i></span>
        @endif
        <div>
            <h6 class="form-section-title">{{ $title }}</h6>
            @if($subtitle)
                <p class="form-section-subtitle">{{ $subtitle }}</p>
            @endif
        </div>
    </header>

    <div class="form-section-body">
        {{ $slot }}
    </div>
</section>
