@props([
    'name',
    'label' => null,
    'options' => [],
    'selected' => null,
    'required' => false,
    'placeholder' => '— Select —',
    'hint' => null,
    'col' => 'col-md-6',
    'searchable' => false,
    // See x-ui.field — nested array names validate under a dotted key.
    'errorKey' => null,
    // One field per line, label on the left. $col is ignored in that mode.
    'horizontal' => false,
])

@php
    $errorKey ??= $name;

    // old() wins so a failed validation round-trip keeps what the user picked.
    $current = old($name, $selected);
@endphp

<div class="{{ $horizontal ? 'row form-line' : $col.' mb-3' }}">
    @if($label)
        <label for="{{ $name }}"
               class="{{ $horizontal ? 'col-sm-4 col-lg-3 col-form-label' : 'form-label' }} fw-semibold">
            {{ $label }}
            @if($required)<span class="req">*</span>@endif
        </label>
    @endif

    <div class="{{ $horizontal ? 'col-sm-8 col-lg-9' : '' }}">
        <select
            id="{{ $name }}"
            name="{{ $name }}"
            @if($required) required @endif
            @if($searchable) data-searchable data-placeholder="{{ $placeholder }}" @endif
            {{ $attributes->merge(['class' => 'form-select'.($errors->has($errorKey) ? ' is-invalid' : '')]) }}
        >
            @if($placeholder !== false)
                <option value="">{{ $placeholder }}</option>
            @endif

            @foreach($options as $value => $text)
                <option value="{{ $value }}" @selected((string) $current === (string) $value)>{{ $text }}</option>
            @endforeach
        </select>

        @error($errorKey)
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror

        @if($hint)
            <div class="form-text">{{ $hint }}</div>
        @endif
    </div>
</div>
