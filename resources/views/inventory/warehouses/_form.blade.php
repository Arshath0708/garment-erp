@php($warehouse = $warehouse ?? null)

<div>
    <label class="form-label">Code</label>
    <input type="text" name="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $warehouse?->code) }}" maxlength="32" required @disabled($warehouse && in_array($warehouse->code, ['MAIN', 'FG'], true))>
    @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
    @if($warehouse && in_array($warehouse->code, ['MAIN', 'FG'], true))
        <input type="hidden" name="code" value="{{ $warehouse->code }}">
        <div class="form-text">Default codes MAIN / FG cannot be renamed.</div>
    @endif
</div>
<div>
    <label class="form-label">Name</label>
    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $warehouse?->name) }}" maxlength="120" required>
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
<div>
    <label class="form-label">Type</label>
    <select name="kind" class="form-select @error('kind') is-invalid @enderror" required>
        @foreach ($kinds as $key => $label)
            <option value="{{ $key }}" @selected(old('kind', $warehouse?->kind ?? 'fabric') === $key)>{{ $label }}</option>
        @endforeach
    </select>
    @error('kind') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
<div class="form-check">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="wh-active" @checked(old('is_active', $warehouse?->is_active ?? true))>
    <label class="form-check-label" for="wh-active">Active</label>
</div>
<div>
    <label class="form-label">Remarks</label>
    <textarea name="remarks" class="form-control" rows="2" maxlength="500">{{ old('remarks', $warehouse?->remarks) }}</textarea>
</div>
