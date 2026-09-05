<x-app-layout>
    <x-slot name="header">Tally</x-slot>

    <x-ui.card title="Tally connection" variant="primary">
        <x-slot name="actions">
            <a href="{{ route('finance.tally.logs') }}" class="btn btn-sm btn-outline-secondary">Posting log</a>
        </x-slot>

        <p class="text-body-secondary small mb-3">
            Tally must be open on the accounts PC with XML on this address (usually port 9000).
            Ledger names below must already exist in that company file. GST e-invoice is still filed on the
            government portal — paste IRN on the export document, then send the sales voucher so Tally stores the IRN in narration.
        </p>

        <form action="{{ route('finance.tally.settings.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-check mb-3">
                <input type="checkbox" name="is_enabled" value="1" id="is_enabled" class="form-check-input"
                       @checked(old('is_enabled', $settings->is_enabled))>
                <label class="form-check-label" for="is_enabled">Allow “Post to Tally” (HTTP). Download XML still works when this is off.</label>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="host_url" class="form-label">Tally XML URL</label>
                    <input type="text" name="host_url" id="host_url" class="form-control @error('host_url') is-invalid @enderror"
                           value="{{ old('host_url', $settings->host_url) }}" required>
                    @error('host_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="company_name" class="form-label">Company name in Tally</label>
                    <input type="text" name="company_name" id="company_name" class="form-control"
                           value="{{ old('company_name', $settings->company_name) }}"
                           placeholder="Same spelling as the Tally company">
                </div>
                <div class="col-md-6">
                    <label for="sales_voucher_type" class="form-label">Sales voucher type</label>
                    <input type="text" name="sales_voucher_type" id="sales_voucher_type" class="form-control"
                           value="{{ old('sales_voucher_type', $settings->sales_voucher_type) }}" required>
                </div>
                <div class="col-md-6">
                    <label for="debit_note_voucher_type" class="form-label">Debit note voucher type</label>
                    <input type="text" name="debit_note_voucher_type" id="debit_note_voucher_type" class="form-control"
                           value="{{ old('debit_note_voucher_type', $settings->debit_note_voucher_type) }}" required>
                </div>
                <div class="col-md-4">
                    <label for="sales_ledger" class="form-label">Sales ledger</label>
                    <input type="text" name="sales_ledger" id="sales_ledger" class="form-control"
                           value="{{ old('sales_ledger', $settings->sales_ledger) }}" required>
                </div>
                <div class="col-md-4">
                    <label for="igst_ledger" class="form-label">IGST ledger</label>
                    <input type="text" name="igst_ledger" id="igst_ledger" class="form-control"
                           value="{{ old('igst_ledger', $settings->igst_ledger) }}" required>
                </div>
                <div class="col-md-4">
                    <label for="job_work_ledger" class="form-label">Job-work ledger (debit notes)</label>
                    <input type="text" name="job_work_ledger" id="job_work_ledger" class="form-control"
                           value="{{ old('job_work_ledger', $settings->job_work_ledger) }}" required>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Save Tally settings</button>
            </div>
        </form>
    </x-ui.card>
</x-app-layout>
