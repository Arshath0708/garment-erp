<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Models\ExportDocument;
use App\Models\ExportDocumentChecklist;
use App\Services\Export\ExportDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * One checklist row's upload / mark-done / reset — reached from the Export
 * Document show page's checklist grid. Gated under export-document.edit:
 * recording a checklist row is an edit of the Export Document it belongs to,
 * not a module of its own.
 */
class ExportDocumentChecklistController extends Controller implements HasMiddleware
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
            new Middleware('permission:export-document.edit', only: ['update', 'reset']),
        ];
    }

    public function update(Request $request, ExportDocument $document, ExportDocumentChecklist $checklist): RedirectResponse
    {
        abort_unless($checklist->export_document_id === $document->id, 404);

        $data = $request->validate([
            'file'         => ['nullable', 'file', 'max:10240'],
            'mark_done'    => ['nullable', 'boolean'],
            'reference_no' => ['nullable', 'string', 'max:120'],
            'amount'       => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'remarks'      => ['nullable', 'string', 'max:1000'],
        ]);

        $this->documents->recordChecklist($checklist, $data);

        return back()->with('success', "\"{$checklist->type->name}\" updated.");
    }

    public function reset(ExportDocument $document, ExportDocumentChecklist $checklist): RedirectResponse
    {
        abort_unless($checklist->export_document_id === $document->id, 404);

        $this->documents->resetChecklist($checklist);

        return back()->with('success', "\"{$checklist->type->name}\" reset to pending.");
    }
}
