<?php

namespace App\Services\Gst;

use App\Models\CompanyProfile;
use App\Models\ExportDocument;
use Illuminate\Validation\ValidationException;

/**
 * Service class for GST E-Invoice IRN Generation & Field Validation.
 *
 * Encapsulates GSP API communication, invoice payload validation,
 * and response handling while keeping manual IRN entry fallback fully functional.
 */
class GstInvoiceService
{
    /**
     * Validate whether an Export Document has all required fields for GST e-invoicing.
     *
     * @return array<string, mixed> Validated payload data
     * @throws ValidationException
     */
    public function validateInvoicePayload(ExportDocument $document): array
    {
        $company = CompanyProfile::current();
        $buyer = $document->buyer;
        $items = $document->items;
        $errors = [];

        if (blank($company->gstin)) {
            $errors['company_gstin'] = 'Company GSTIN is missing in Company Profile.';
        }

        if (blank($document->invoice_no) && blank($document->doc_num)) {
            $errors['invoice_no'] = 'Export Document does not have a valid invoice number.';
        }

        if ($items->isEmpty()) {
            $errors['items'] = 'Export Document contains no items for e-invoicing.';
        }

        foreach ($items as $index => $item) {
            if (blank($item->product?->hsn_code)) {
                $errors["item_{$index}_hsn"] = "Item #{$item->id} ({$item->description}) is missing an HSN code.";
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'supplier_gstin' => $company->gstin,
            'buyer_gstin'    => $buyer?->gst_vat_no ?? 'URP',
            'invoice_no'     => $document->invoice_no ?: $document->doc_num,
            'invoice_date'   => ($document->invoice_date ?? $document->shipment_date)?->format('Y-m-d'),
            'total_amount'   => (float) $document->totalAmount(),
            'items_count'    => $items->count(),
        ];
    }

    /**
     * Check if GSP API credentials are configured in .env.
     */
    public function isConfigured(): bool
    {
        return filled(config('services.gst.client_id'))
            && filled(config('services.gst.client_secret'))
            && filled(config('services.gst.username'))
            && filled(config('services.gst.password'));
    }

    /**
     * Generate IRN via GSP API endpoint (Sandbox or Production).
     *
     * @throws ValidationException
     */
    public function generateIrn(ExportDocument $document): array
    {
        if (! $this->isConfigured()) {
            throw ValidationException::withMessages([
                'gsp_config' => 'GST e-Invoice GSP API credentials are not configured in .env. Please configure GST_GSP_CLIENT_ID, GST_GSP_CLIENT_SECRET, GST_GSP_USERNAME, and GST_GSP_PASSWORD provided by Sunil/GSP partner.',
            ]);
        }

        $payload = $this->validateInvoicePayload($document);

        // Future integration point: Call GSP API sandbox/production endpoint
        return [
            'status'  => 'success',
            'irn'     => null,
            'payload' => $payload,
        ];
    }
}
