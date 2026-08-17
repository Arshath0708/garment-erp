<?php

namespace App\Http\Controllers\Export;

use App\Http\Controllers\Controller;
use App\Http\Requests\Export\ExtractDocumentRequest;
use App\Models\DocumentChecklistType;
use App\Models\ExportDocument;
use App\Models\ExportDocumentChecklist;
use App\Services\Export\ExportDocumentService;
use App\Services\Export\GeminiDocumentExtractor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

/**
 * Standalone Document OCR desk — sits under Export, after Export Documents.
 * Phase 1: only sheet #4 CHA Checklist (uploaded).
 */
class ExportDocumentOcrController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly GeminiDocumentExtractor $ocr,
        private readonly ExportDocumentService $documents,
    ) {
    }

    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:export-document.view', only: ['index']),
            new Middleware('permission:export-document.edit', only: ['extract', 'store']),
        ];
    }

    public function index(Request $request): View
    {
        $selectedDocId = $request->integer('export_document_id') ?: null;
        $typeCode = $request->string('type_code')->toString() ?: 'cha_checklist';

        if (! in_array($typeCode, GeminiDocumentExtractor::PHASE1_TYPES, true)) {
            $typeCode = 'cha_checklist';
        }

        $documents = ExportDocument::query()
            ->with('buyer:id,company_name,display_code')
            ->orderByDesc('id')
            ->get();

        $selected = $selectedDocId
            ? $documents->firstWhere('id', $selectedDocId)
            : $documents->first();

        $checklist = null;
        if ($selected) {
            $type = DocumentChecklistType::query()->where('code', $typeCode)->first();
            if ($type) {
                $checklist = ExportDocumentChecklist::query()
                    ->where('export_document_id', $selected->id)
                    ->where('document_checklist_type_id', $type->id)
                    ->first();
            }
        }

        return view('export.ocr.index', [
            'documents'      => $documents,
            'selected'       => $selected,
            'typeCode'       => $typeCode,
            'typeLabels'     => GeminiDocumentExtractor::phase1Labels(),
            'checklist'      => $checklist,
            'ocrConfigured'  => $this->ocr->isConfigured(),
            'upcomingTypes'  => [
                '#5 E-Sanchit Documents',
                '#9 Assessed Copy',
                '#10 LEO Copy',
                '#13 CLP',
                '#15 Bill of Lading (Final)',
                '#16 Insurance Certificate',
                '#20–23 Payment / FIRC / Bank Certificate / eBRC',
            ],
        ]);
    }

    public function extract(ExtractDocumentRequest $request): JsonResponse
    {
        $typeCode = $request->string('type_code')->toString();

        if (! in_array($typeCode, GeminiDocumentExtractor::PHASE1_TYPES, true)) {
            return response()->json([
                'message' => 'Only Phase 1 uploaded types are enabled here (start with #4 CHA Checklist).',
            ], 422);
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

        return response()->json($result);
    }

    /**
     * Save the uploaded scan + OCR fields onto the matching checklist row.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'export_document_id' => ['required', 'integer', 'exists:export_documents,id'],
            'type_code'          => ['required', 'string', 'in:'.implode(',', GeminiDocumentExtractor::PHASE1_TYPES)],
            'file'               => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,gif,pdf'],
            'reference_no'       => ['nullable', 'string', 'max:120'],
            'remarks'            => ['nullable', 'string', 'max:1000'],
        ]);

        $document = ExportDocument::query()->findOrFail($data['export_document_id']);
        $type = DocumentChecklistType::query()->where('code', $data['type_code'])->firstOrFail();

        $checklist = ExportDocumentChecklist::query()
            ->where('export_document_id', $document->id)
            ->where('document_checklist_type_id', $type->id)
            ->first();

        if (! $checklist) {
            $this->documents->ensureChecklist($document);
            $checklist = ExportDocumentChecklist::query()
                ->where('export_document_id', $document->id)
                ->where('document_checklist_type_id', $type->id)
                ->firstOrFail();
        }

        $this->documents->recordChecklist($checklist, [
            'file'         => $request->file('file'),
            'mark_done'    => true,
            'reference_no' => $data['reference_no'] ?? null,
            'remarks'      => $data['remarks'] ?? null,
        ]);

        return redirect()
            ->route('export.ocr.index', [
                'export_document_id' => $document->id,
                'type_code'          => $data['type_code'],
            ])
            ->with('success', "\"{$type->name}\" saved from OCR for {$document->doc_num}.");
    }
}
