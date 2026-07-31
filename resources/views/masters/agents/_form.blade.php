@props([
    'agent' => null,
    'agentTypes' => [],
    'categories' => [],
    'calculationBases' => [],
])

<x-ui.form-section title="Agent Details" icon="bi-person-badge"
                   subtitle="Manage agent classifications, unique identifiers, and commission configurations.">
    <div class="form-stack">

        {{-- Agent Type --}}
        <x-ui.select name="agent_type" label="Agent Type" required horizontal
                     :options="$agentTypes" :selected="$agent?->agent_type ?? 'supplier'"
                     :placeholder="false" />

        {{-- Agent Name --}}
        <x-ui.field name="name" label="Agent Name" :value="$agent?->name" required
                    horizontal placeholder="E.g. John Doe & Co." />

        {{-- Display Code (max 5 chars) --}}
        <div class="row form-line">
            <label for="display_code" class="col-sm-4 col-lg-3 col-form-label fw-semibold">
                Display Code <span class="req">*</span>
            </label>
            <div class="col-sm-8 col-lg-9">
                <input type="text" id="display_code" name="display_code" maxlength="5" required
                       value="{{ old('display_code', $agent?->display_code) }}"
                       class="form-control js-unique-check @error('display_code') is-invalid @enderror"
                       data-field="display_code" placeholder="AGT01" autocomplete="off">
                @error('display_code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <div class="form-text js-unique-feedback"></div>
            </div>
        </div>

        {{-- Category (Multi-select) --}}
        <div class="row form-line">
            <label for="categories" class="col-sm-4 col-lg-3 col-form-label fw-semibold">
                Categories
            </label>
            <div class="col-sm-8 col-lg-9">
                <select id="categories" name="categories[]" multiple data-searchable data-placeholder="Select categories…"
                        class="form-select @error('categories') is-invalid @enderror">
                    @foreach($categories as $id => $name)
                        <option value="{{ $id }}" @selected(in_array($id, old('categories', $agent?->categories->pluck('id')->all() ?? [])))>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
                @error('categories')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <div class="form-text">Choose the product categories handled by this agent.</div>
            </div>
        </div>

        {{-- Commission Basis --}}
        <x-ui.select name="calculation_basis_id" label="Commission Basis" horizontal searchable
                     :options="$calculationBases" :selected="$agent?->calculation_basis_id"
                     placeholder="Search commission basis…" />

        {{-- Commission Rate (Reserved for future use) --}}
        <x-ui.field name="commission_rate" label="Commission Rate (Optional)" type="number" step="0.01" min="0"
                    :value="$agent?->commission_rate" horizontal
                    placeholder="0.00" hint="Optional rate. Reserved for future business calculations." />

        {{-- Status --}}
        <x-ui.select name="status" label="Status" required horizontal
                     :options="['active' => 'Active', 'inactive' => 'Inactive']"
                     :selected="$agent?->status ?? 'active'"
                     :placeholder="false" />

        {{-- Remarks --}}
        <x-ui.textarea name="remarks" label="Remarks" :value="$agent?->remarks"
                       horizontal rows="2" placeholder="Optional notes" />

    </div>
</x-ui.form-section>

<div class="form-actions">
    <button type="submit" class="btn btn-primary px-4">
        <i class="bi bi-check-lg me-1"></i>{{ $agent ? 'Update' : 'Save' }} Agent
    </button>
    <a href="{{ route('masters.agents.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkUrl = @json(route('masters.agents.check-code'));
    const ignoreId = @json($agent?->id);
    const original = new Map();

    document.querySelectorAll('.js-unique-check').forEach(function (input) {
        original.set(input, input.value.trim());
        const feedback = input.parentElement.querySelector('.js-unique-feedback');
        let timer = null;

        function clearFeedback() {
            feedback.textContent = '';
            feedback.className = 'form-text js-unique-feedback';
        }

        input.addEventListener('input', function () {
            clearTimeout(timer);
            const value = input.value.trim();

            if (value === '' || value === original.get(input)) {
                clearFeedback();
                input.classList.remove('is-invalid', 'is-valid');
                return;
            }

            timer = setTimeout(function () {
                const params = new URLSearchParams({ field: input.dataset.field, value: value });
                if (ignoreId) params.append('ignore', ignoreId);

                fetch(checkUrl + '?' + params, { headers: { 'Accept': 'application/json' } })
                    .then(r => r.ok ? r.json() : Promise.reject())
                    .then(function (data) {
                        input.classList.toggle('is-invalid', !data.available);
                        input.classList.toggle('is-valid', data.available);
                        feedback.textContent = data.available
                            ? 'Available.'
                            : 'Already taken — choose another.';
                        feedback.className = 'form-text js-unique-feedback ' +
                            (data.available ? 'text-success' : 'text-danger');
                    })
                    .catch(clearFeedback);
            }, 350);
        });
    });
});
</script>
@endpush
