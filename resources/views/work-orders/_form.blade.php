@props(['workOrder' => null])

@php
    $val = fn (string $field, $default = null) => old($field, $workOrder?->{$field} ?? $default);
    $target = $val('target_date');
    if ($target instanceof \Carbon\CarbonInterface) {
        $target = $target->format('Y-m-d');
    }
    $woDate = $val('wo_date');
    if ($woDate instanceof \Carbon\CarbonInterface) {
        $woDate = $woDate->format('Y-m-d');
    }
@endphp

<x-ui.form-section title="Work order" icon="bi-clipboard-check"
                   subtitle="Release this before Production Planning. T&amp;A dates count back from Target date.">
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label fw-semibold">WO No.</label>
            <input type="text" class="form-control bg-body-tertiary" readonly
                   value="{{ $workOrder?->wo_num ?? 'Auto on save' }}">
        </div>
        <x-ui.field name="wo_date" label="Date" type="date" required col="col-md-4"
                    :value="$woDate ?? now()->format('Y-m-d')" />
        <x-ui.field name="target_date" label="Target / delivery date" type="date" required col="col-md-4"
                    :value="$target ?? now()->addDays(30)->format('Y-m-d')"
                    hint="Cutting, stitch, pack dates are planned from this date." />
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold required">Garment Style</label>
            <select name="garment_style_id" class="form-select @error('garment_style_id') is-invalid @enderror" required>
                <option value="">— Select —</option>
                @foreach($styles as $style)
                    <option value="{{ $style->id }}" @selected((string) $val('garment_style_id') === (string) $style->id)>
                        {{ $style->style_number }} — {{ $style->name }}
                        @if($style->buyer) ({{ $style->buyer->company_name }}) @endif
                    </option>
                @endforeach
            </select>
            @error('garment_style_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Buyer Sales Order / OC</label>
            <select name="order_confirmation_id" class="form-select @error('order_confirmation_id') is-invalid @enderror">
                <option value="">— Optional —</option>
                @foreach($salesOrders as $so)
                    <option value="{{ $so->id }}" @selected((string) $val('order_confirmation_id') === (string) $so->id)>
                        {{ $so->oc_num }}
                        @if($so->buyer) ({{ $so->buyer->company_name }}) @endif
                    </option>
                @endforeach
            </select>
            @error('order_confirmation_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="row">
        <x-ui.field name="total_qty" label="Quantity (pcs)" type="number" required col="col-md-4"
                    :value="$val('total_qty')" />
        <x-ui.textarea name="notes" label="Notes" col="col-md-8" rows="2" :value="$val('notes')"
                       placeholder="Trim not in, hold cutting, etc." />
    </div>
</x-ui.form-section>
