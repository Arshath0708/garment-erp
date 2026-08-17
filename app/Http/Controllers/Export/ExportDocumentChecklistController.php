<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Http\Requests\Export\ExtractDocumentRequest;
use App\Models\ExportDocument;
use App\Models\ExportDocumentChecklist;
use App\Services\Export\ExportDocumentService;
use App\Services\Export\GeminiDocumentExtractor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use RuntimeException;
use Throwable;

/**
 * One checklist row's upload / mark-done / reset / OCR — reached from the
 * Export Document show page's checklist grid. Gated under export-document.edit:
 * recording a checklist row is an edit of the Export Document it belongs to,
 * not a module of its own.
 */
class ExportDocumentChecklistController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly ExportDocumentService $documents,
        private readonly GeminiDocumentExtractor $ocr,
    ) {
    }

    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:export-document.edit', only: ['update', 'reset', 'extract']),
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

    /**
     * Run Gemini OCR on an uploaded scan and return suggested checklist fields.
     * Does not save — the user reviews then clicks Save on the checklist form.
     */
    public function extract(
        ExtractDocumentRequest $request,
        ExportDocument $document,
        ExportDocumentChecklist $checklist,
    ): JsonResponse {
        abort_unless($checklist->export_document_id === $document->id, 404);

        $checklist->loadMissing('type');
        $typeCode = $checklist->type?->code;

        if ($typeCode !== $request->string('type_code')->toString()) {
            return response()->json(['message' => 'Checklist type does not match the request.'], 422);
        }

        if (! $this->ocr->isConfigured()) {
            return response()->json([
                'message' => 'Gemini is not configured. Add GEMINI_API_KEY to your .env file.',
            ], 503);
        }

        try {
            $result = $this->ocr->extract($request->file('file'), $typeCode);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'OCR failed unexpectedly. Try again or enter fields manually.'], 500);
        }

        return response()->json([
            'reference_no' => $result['reference_no'],
            'remarks'      => $result['remarks'],
            'fields'       => $result['fields'],
        ]);
    }
}
