<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Http\Requests\Export\UpdateExportDocumentRequest;
use App\Models\Buyer;
use App\Models\CompanyProfile;
use App\Models\ExportDocument;
use App\Models\Incoterm;
use App\Models\OrderConfirmation;
use App\Models\Port;
use App\Models\ShipmentMethod;
use App\Services\Export\ExportDocumentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;

class ExportDocumentController extends Controller implements HasMiddleware
{
    public function __construct(private readonly ExportDocumentService $documents)
    {
    }

    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:export-document.view', only: ['index', 'show']),
            new Middleware('permission:export-document.create', only: ['raiseFromOrderConfirmation']),
            new Middleware('permission:export-document.edit', only: ['edit', 'update']),
            new Middleware('permission:export-document.delete', only: ['destroy']),
            new Middleware('permission:export-document.generate', only: ['deliveryChallanPdf', 'eInvoicePdf']),
        ];
    }

    public function index(Request $request): View
    {
        $documents = ExportDocument::query()
            ->with(['buyer:id,company_name,display_code', 'orderConfirmation:id,oc_num', 'checklist'])
            ->when(
                $request->filled('buyer_id'),
                fn ($q) => $q->where('buyer_id', $request->integer('buyer_id'))
            )
            ->search($request->string('search')->toString())
            ->status($request->string('status')->toString())
            ->sort($request->string('sort')->toString(), $request->string('direction')->toString())
            ->paginate(15)
            ->withQueryString();

        return view('export.documents.index', [
            'documents' => $documents,
            'buyers'    => Buyer::active()->orderBy('company_name')->get()->pluck('label', 'id'),
            'statuses'  => ExportDocument::STATUSES,
            'filters'   => $request->only('search', 'status', 'buyer_id', 'sort', 'direction'),
        ]);
    }

    public function show(ExportDocument $document): View
    {
        return view('export.documents.show', [
            'document' => $document->load([
                'orderConfirmation', 'buyer', 'currency', 'incoterm',
                'portOfLoading', 'portOfDischarge', 'shipmentMethod',
                'items' => fn ($q) => $q->with(['product', 'colours.sizes']),
                'checklist' => fn ($q) => $q->with('type')->orderBy('id'),
                'creator', 'updater',
            ]),
        ]);
    }

    public function edit(ExportDocument $document): View
    {
        return view('export.documents.edit', [
            'document'        => $document,
            'incoterms'       => Incoterm::active()->orderBy('code')->pluck('code', 'id'),
            'ports'           => Port::active()->orderBy('name')->pluck('name', 'id'),
            'shipmentMethods' => ShipmentMethod::active()->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function update(UpdateExportDocumentRequest $request, ExportDocument $document): RedirectResponse
    {
        $document->update($request->validated());

        return redirect()
            ->route('export.documents.show', $document)
            ->with('success', "Export Document \"{$document->doc_num}\" updated successfully.");
    }

    /**
     * Delivery Challan — sent to the customs department when goods go to the
     * docks/airport. One fixed layout, no variants.
     */
    public function deliveryChallanPdf(ExportDocument $document): Response
    {
        $document = $this->loadForInvoiceDocument($document);
        $company = CompanyProfile::current();

        $pdf = Pdf::loadView('export.documents.delivery-challan', compact('document', 'company'));
        $filename = $this->documentFilename($document, 'delivery-challan');

        $this->documents->attachGeneratedFile(
            $document,
            'delivery_challan',
            null,
            $this->storeGeneratedPdf($pdf, $document, 'delivery-challan'),
            $filename
        );

        return $pdf->download($filename);
    }

    /**
     * E-Invoice — the export invoice's content, ready to submit to the
     * government e-invoice portal. The IRN/Ack No./QR code the portal
     * returns are outside this system — recorded on the checklist row's
     * reference number once the portal issues them, same as any other
     * uploaded document.
     */
    public function eInvoicePdf(ExportDocument $document): Response
    {
        $document = $this->loadForInvoiceDocument($document);
        $company = CompanyProfile::current();

        $pdf = Pdf::loadView('export.documents.e-invoice', compact('document', 'company'));
        $filename = $this->documentFilename($document, 'e-invoice');

        $this->documents->attachGeneratedFile(
            $document,
            'e_invoice',
            null,
            $this->storeGeneratedPdf($pdf, $document, 'e-invoice'),
            $filename
        );

        return $pdf->download($filename);
    }

    private function loadForInvoiceDocument(ExportDocument $document): ExportDocument
    {
        return $document->load([
            'orderConfirmation', 'buyer', 'currency', 'incoterm',
            'portOfLoading', 'portOfDischarge', 'shipmentMethod',
            'items' => fn ($q) => $q->with(['product.category', 'colours.sizes']),
        ]);
    }

    /** Same reasoning as InquiryController::documentFilename() — doc_num contains "/", invalid in a download filename. */
    private function documentFilename(ExportDocument $document, string $slug): string
    {
        return str_replace('/', '-', $document->doc_num)."-{$slug}.pdf";
    }

    private function storeGeneratedPdf(PdfDocument $pdf, ExportDocument $document, string $slug): string
    {
        $path = "export-documents/{$document->id}/{$slug}-".now()->timestamp.'.pdf';
        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    /**
     * "Raise Export Document for Selected" — reached from the OC show page,
     * same shape as OrderConfirmationController::raisePurchaseOrders().
     */
    public function raiseFromOrderConfirmation(Request $request, OrderConfirmation $orderConfirmation): RedirectResponse
    {
        if ($orderConfirmation->status !== 'confirmed') {
            return back()->with('warning', 'The buyer has not confirmed this OC yet — mark it Confirmed before raising an Export Document.');
        }

        $itemIds = array_map('intval', (array) $request->input('item_ids', []));

        if (empty($itemIds)) {
            return back()->with('warning', 'Select at least one item to raise an Export Document.');
        }

        try {
            $document = $this->documents->raiseFromOrderConfirmation($orderConfirmation, $itemIds);
        } catch (RuntimeException $e) {
            return back()->with('warning', $e->getMessage());
        }

        return redirect()
            ->route('export.documents.show', $document)
            ->with('success', "Export Document \"{$document->doc_num}\" raised with the 26-point checklist ready to track.");
    }

    public function destroy(ExportDocument $document): RedirectResponse
    {
        $document->delete();

        return redirect()
            ->route('export.documents.index')
            ->with('success', "Export Document \"{$document->doc_num}\" deleted successfully.");
    }
}
