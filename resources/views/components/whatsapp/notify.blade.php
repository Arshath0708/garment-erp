@props([
    'purchaseOrder' => null,
    'step' => null,
    'compact' => false,
])

@php
    $whatsapp = app(\App\Services\Communication\WhatsappService::class);
    if ($purchaseOrder) {
        $action = route('whatsapp.purchase-orders', $purchaseOrder);
        $digits = $whatsapp->supplierDigits($purchaseOrder);
    } else {
        $action = route('whatsapp.tna-steps', $step);
        $digits = $whatsapp->buyerDigits($step);
    }
@endphp

@can('whatsapp.send')
    @if($compact)
        <form action="{{ $action }}" method="POST" class="d-flex flex-wrap gap-1 align-items-center justify-content-end">
            @csrf
            <input type="text" name="mobile" value="{{ $digits }}"
                   class="form-control form-control-sm" style="width:8.5rem"
                   placeholder="Mobile" inputmode="tel" autocomplete="off">
            <button type="submit" name="mode" value="wa_me" class="btn btn-sm btn-success" title="Open WhatsApp">
                <i class="bi bi-whatsapp"></i>
            </button>
            <button type="submit" name="mode" value="api" class="btn btn-sm btn-outline-success" title="Send via Cloud API">
                API
            </button>
        </form>
    @else
        <div class="border rounded p-3 bg-body-tertiary">
            <h6 class="fw-semibold mb-2">
                <i class="bi bi-whatsapp me-1"></i> WhatsApp
            </h6>
            <p class="text-body-secondary small mb-3">
                Opens a chat with the message filled in. Cloud API send needs a token on
                @can('whatsapp.view')
                    <a href="{{ route('whatsapp.settings') }}">WhatsApp settings</a>.
                @else
                    WhatsApp settings.
                @endcan
            </p>
            <form action="{{ $action }}" method="POST" class="row g-2 align-items-end">
                @csrf
                <div class="col-sm-6">
                    <label class="form-label small mb-1">Mobile</label>
                    <input type="text" name="mobile" value="{{ $digits }}"
                           class="form-control form-control-sm" placeholder="10-digit or with country code" inputmode="tel">
                </div>
                <div class="col-sm-6 d-flex flex-wrap gap-2">
                    <button type="submit" name="mode" value="wa_me" class="btn btn-sm btn-success">
                        <i class="bi bi-whatsapp me-1"></i> Open WhatsApp
                    </button>
                    <button type="submit" name="mode" value="api" class="btn btn-sm btn-outline-success">
                        Send via API
                    </button>
                </div>
            </form>
        </div>
    @endif
@endcan
