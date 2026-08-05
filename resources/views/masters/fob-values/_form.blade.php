<div class="row g-3">
    <div class="col-md-6">
        <label for="name" class="form-label required">FOB Value Name</label>
        <input type="text"
               name="name"
               id="name"
               value="{{ old('name', $fobValue->name ?? '') }}"
               class="form-control @error('name') is-invalid @enderror"
               placeholder="e.g. FOB Value, Net Value, Gross Value, CIF Value, Ex Factory"
               required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label for="status" class="form-label required">Status</label>
        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
            <option value="active" @selected(old('status', $fobValue->status ?? 'active') === 'active')>Active</option>
            <option value="inactive" @selected(old('status', $fobValue->status ?? 'active') === 'inactive')>Inactive</option>
        </select>
        @error('status')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="remarks" class="form-label">Remarks</label>
        <textarea name="remarks"
                  id="remarks"
                  rows="3"
                  class="form-control @error('remarks') is-invalid @enderror"
                  placeholder="Optional internal remarks or notes">{{ old('remarks', $fobValue->remarks ?? '') }}</textarea>
        @error('remarks')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
