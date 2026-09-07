<?php

namespace App\Services\Sales;

use App\Models\ExportDocument;
use App\Models\Inquiry;
use App\Models\OrderConfirmation;
use App\Services\Export\ExportDocumentService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * One path: confirmed enquiry → sales order (OC) → export document with invoice no.
 * No re-typing buyer, items, prices, or shipment date.
 */
class OfferToInvoiceService
{
    public function __construct(
        private readonly OrderConfirmationService $confirmations,
        private readonly ExportDocumentService $documents,
    ) {
    }

    /**
     * Convert confirmed enquiry lines to a confirmed OC and raise one Export Document.
     */
    public function fromInquiry(Inquiry $inquiry): ExportDocument
    {
        return DB::transaction(function () use ($inquiry) {
            $oc = $this->confirmations->convertFromInquiry($inquiry);
            $oc->update(['status' => 'confirmed']);

            return $this->raiseInvoiceDocument($oc->fresh('items'));
        });
    }

    /**
     * Confirm the OC if needed, then raise Export Document for every unshipped line.
     */
    public function fromOrderConfirmation(OrderConfirmation $oc, bool $confirmIfDraft = true): ExportDocument
    {
        return DB::transaction(function () use ($oc, $confirmIfDraft) {
            $oc = $oc->fresh('items');

            if ($oc->status !== 'confirmed') {
                if (! $confirmIfDraft) {
                    throw new RuntimeException('Mark the OC Confirmed before raising an invoice.');
                }
                if (! in_array($oc->status, ['draft', 'sent'], true)) {
                    throw new RuntimeException('This OC cannot be confirmed from status '.$oc->status.'.');
                }
                $oc->update(['status' => 'confirmed']);
                $oc = $oc->fresh('items');
            }

            return $this->raiseInvoiceDocument($oc);
        });
    }

    private function raiseInvoiceDocument(OrderConfirmation $oc): ExportDocument
    {
        $itemIds = $oc->items()
            ->whereNull('export_document_id')
            ->pluck('id')
            ->all();

        if ($itemIds === []) {
            throw new RuntimeException('Nothing to invoice — every line is already on an Export Document.');
        }

        return $this->documents->raiseFromOrderConfirmation($oc, $itemIds);
    }
}
