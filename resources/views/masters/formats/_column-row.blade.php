{{--
    One row of the Order Format column builder — used both for the initial
    server-rendered rows and, verbatim, inside #column-row-template for a
    custom column added client-side (see _form.blade.php's add-custom-column
    handler, which swaps __KEY__ for a generated id).
--}}
<tr class="column-row" data-key="{{ $row['key'] }}">
    <td class="column-drag-handle" draggable="true" title="Drag to reorder">
        <i class="bi bi-grip-vertical"></i>
    </td>
    <td>
        <input type="hidden" name="columns[{{ $row['key'] }}][enabled]" value="">
        <input class="form-check-input js-column-toggle" type="checkbox"
               id="col-{{ $row['key'] }}"
               name="columns[{{ $row['key'] }}][enabled]" value="1"
               @checked($row['enabled'])>
    </td>
    <td>
        <input type="hidden" name="columns[{{ $row['key'] }}][mandatory]" value="">
        <input class="form-check-input js-column-mandatory" type="checkbox"
               name="columns[{{ $row['key'] }}][mandatory]" value="1"
               @checked($row['mandatory'])>
    </td>
    <td class="col-name">
        @if($row['is_custom'])
            <input type="text" class="form-control form-control-sm js-column-label"
                   name="columns[{{ $row['key'] }}][label]" value="{{ $row['label'] }}"
                   maxlength="60" placeholder="Column name">
            <input type="hidden" name="columns[{{ $row['key'] }}][is_custom]" value="1">
        @else
            <label for="col-{{ $row['key'] }}" class="scheme-name mb-1 d-block">
                {{ \App\Models\DocumentFormatColumn::STANDARD[$row['key']]['label'] ?? $row['key'] }}
            </label>
            <input type="text" class="form-control form-control-sm js-column-label"
                   name="columns[{{ $row['key'] }}][label]" value="{{ $row['label'] }}"
                   maxlength="60" placeholder="Label on document">
        @endif
        @error("columns.{$row['key']}.label")
            <div class="cell-error">{{ $message }}</div>
        @enderror
    </td>
    <td class="subcol-cell">
        @if($row['key'] === 'size')
            <div class="subcol-chips d-flex flex-wrap gap-1 mb-1" style="min-height:1.6rem">
                @foreach($row['sub_columns'] as $tag)
                    <span class="unit-chip subcol-chip" data-tag="{{ $tag }}">
                        {{ $tag }}
                        <button type="button" class="unit-chip-remove js-subcol-remove" aria-label="Remove {{ $tag }}">&times;</button>
                    </span>
                @endforeach
            </div>
            <input type="hidden" class="js-subcol-value" name="columns[size][sub_columns]"
                   value="{{ implode(',', $row['sub_columns']) }}">
            <div class="d-flex gap-1">
                <input type="text" class="form-control form-control-sm js-subcol-input"
                       placeholder="e.g. S, M, L or 28-30-32" autocomplete="off" style="max-width:12rem">
                <button type="button" class="btn btn-sm btn-outline-secondary js-subcol-add">+</button>
            </div>
            <div class="mt-1">
                <button type="button" class="btn btn-sm btn-outline-secondary js-subcol-preset" data-preset="S,M,L,XL">S-M-L-XL</button>
                <button type="button" class="btn btn-sm btn-outline-secondary js-subcol-preset" data-preset="28,30,32,34,36">28-36</button>
            </div>
        @else
            <span class="na">—</span>
        @endif
    </td>
    <td class="small text-body-secondary">
        {{ $row['print_only'] ? 'Print / PDF only' : 'Screen + print' }}
    </td>
    <td>
        @if($row['is_custom'])
            <button type="button" class="btn btn-sm btn-outline-danger js-remove-column">
                <i class="bi bi-trash"></i>
            </button>
        @endif
    </td>
</tr>
