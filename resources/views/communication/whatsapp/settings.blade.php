<x-app-layout>
    <x-slot name="header">WhatsApp</x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-9">
            <x-ui.card title="WhatsApp alerts" variant="primary">
                <x-slot name="actions">
                    <a href="{{ route('whatsapp.logs') }}" class="btn btn-sm btn-outline-secondary">Message log</a>
                </x-slot>

                <p class="text-body-secondary small mb-4">
                    Send a purchase order notice to the supplier, or a late Time &amp; Action alert to the buyer.
                    <strong>Open WhatsApp</strong> always works — it fills the chat in the browser or the app.
                    Cloud API is optional and only needed if you want the ERP to send without opening a window.
                    Meta only delivers free-form text if that number has messaged you in the last 24 hours;
                    otherwise use Open WhatsApp, or a pre-approved template later.
                </p>

                <form action="{{ route('whatsapp.settings.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="form-check form-switch mb-4">
                        <input type="checkbox" class="form-check-input" role="switch"
                               name="is_enabled" id="is_enabled" value="1"
                               @checked(old('is_enabled', $settings->is_enabled))>
                        <label class="form-check-label" for="is_enabled">Enable Cloud API send</label>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="phone_number_id" class="form-label">Phone number ID</label>
                            <input type="text" name="phone_number_id" id="phone_number_id"
                                   value="{{ old('phone_number_id', $settings->phone_number_id) }}"
                                   class="form-control @error('phone_number_id') is-invalid @enderror"
                                   placeholder="From Meta WhatsApp Cloud API">
                            @error('phone_number_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-3">
                            <label for="graph_version" class="form-label">Graph version</label>
                            <input type="text" name="graph_version" id="graph_version"
                                   value="{{ old('graph_version', $settings->graph_version) }}"
                                   class="form-control @error('graph_version') is-invalid @enderror" required>
                            @error('graph_version') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-3">
                            <label for="country_code" class="form-label">Default country code</label>
                            <input type="text" name="country_code" id="country_code"
                                   value="{{ old('country_code', $settings->country_code) }}"
                                   class="form-control @error('country_code') is-invalid @enderror" required>
                            @error('country_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Prepended when the contact has a 10-digit mobile.</div>
                        </div>

                        <div class="col-12">
                            <label for="access_token" class="form-label">Access token</label>
                            <input type="password" name="access_token" id="access_token" autocomplete="off"
                                   class="form-control @error('access_token') is-invalid @enderror"
                                   placeholder="{{ $settings->hasToken() ? 'Token saved — leave blank to keep it' : 'Paste a new token' }}">
                            @error('access_token') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Stored encrypted. Never shown again after save. You can also set WHATSAPP_ACCESS_TOKEN in .env.</div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i> Save
                        </button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
