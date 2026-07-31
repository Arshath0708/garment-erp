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
    // Pick several. Posts as name[], so $selected is an array of values.
    // Buyer sheet col D: "allow multiple selection in a drop down menu".
    'multiple' => false,
    // See x-ui.field — nested array names validate under a dotted key.
    'errorKey' => null,
    // One field per line, label on the left. $col is ignored in that mode.
    'horizontal' => false,
])

@php
    $errorKey ??= $name;

    // old() wins so a failed validation round-trip keeps what the user picked.
    $current = old($name, $selected);

    // Compared as strings throughout: a selected value may arrive as an int
    // from the model and as a string from old(), and 1 == "1" is not something
    // to leave to loose comparison inside a loop.
    $chosen = $multiple
        ? collect($current)->map(fn ($value) => (string) $value)->all()
        : [(string) $current];
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
            name="{{ $name }}{{ $multiple ? '[]' : '' }}"
            @if($multiple) multiple @endif
            @if($required) required @endif
            @if($searchable) data-searchable data-placeholder="{{ $placeholder }}" @endif
            {{ $attributes->merge(['class' => 'form-select'.($errors->has($errorKey) ? ' is-invalid' : '')]) }}
        >
            {{-- A multi-select has no empty choice: picking nothing is how you
                 clear it, and a blank option would post an empty string that
                 then has to be filtered back out. --}}
            @if($placeholder !== false && ! $multiple)
                <option value="">{{ $placeholder }}</option>
            @endif

            @foreach($options as $value => $text)
                <option value="{{ $value }}" @selected(in_array((string) $value, $chosen, true))>{{ $text }}</option>
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
