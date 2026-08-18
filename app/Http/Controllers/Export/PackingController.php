<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\ExportDocument;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PackingController extends Controller
{
    public function index(Request $request): View
    {
        $documents = ExportDocument::query()
            ->with([
                'buyer:id,company_name,display_code',
                'cartons.lines',
                'checklist' => fn ($q) => $q->with('type:id,code,variant_labels'),
            ])
            ->when(
                $request->filled('search'),
                fn ($q) => $q->search($request->string('search')->toString())
            )
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('export.packing.index', compact('documents'));
    }

    public function show(ExportDocument $document): View
    {
        $document->load([
            'buyer:id,company_name,display_code',
            'cartons.lines',
            'checklist' => fn ($q) => $q->with('type:id,code,name,variant_labels'),
        ]);

        $packingRows = $document->checklist
            ->filter(fn ($row) => $row->type?->code === 'packing_list')
            ->values();

        return view('export.packing.show', compact('document', 'packingRows'));
    }
}
