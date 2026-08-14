<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\ExportDocument;
use App\Models\OrderConfirmation;
use App\Services\Export\ExportDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
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
            new Middleware('permission:export-document.delete', only: ['destroy']),
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
