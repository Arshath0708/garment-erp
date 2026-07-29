@props([
    'name',
    'label' => null,
    'value' => null,
    'rows' => 3,
    'required' => false,
    'hint' => null,
    'placeholder' => null,
    'col' => 'col-md-6',
    // One field per line, label on the left. $col is ignored in that mode.
    'horizontal' => false,
])

<div class="{{ $horizontal ? 'row form-line' : $col.' mb-3' }}">
    @if($label)
        <label for="{{ $name }}"
               class="{{ $horizontal ? 'col-sm-4 col-lg-3 col-form-label' : 'form-label' }} fw-semibold">
            {{ $label }}
            @if($required)<span class="req">*</span>@endif
        </label>
    @endif

    <div class="{{ $horizontal ? 'col-sm-8 col-lg-9' : '' }}">
        <textarea
            id="{{ $name }}"
            name="{{ $name }}"
            rows="{{ $rows }}"
            placeholder="{{ $placeholder }}"
            @if($required) required @endif
            {{ $attributes->merge(['class' => 'form-control'.($errors->has($name) ? ' is-invalid' : '')]) }}
        >{{ old($name, $value) }}</textarea>

        @error($name)
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror

        @if($hint)
            <div class="form-text">{{ $hint }}</div>
        @endif
    </div>
</div>
