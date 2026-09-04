<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\UpdateTallySettingsRequest;
use App\Models\DebitNote;
use App\Models\ExportDocument;
use App\Models\TallyPostLog;
use App\Models\TallySetting;
use App\Services\Finance\TallyPostService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TallyController extends Controller implements HasMiddleware
{
    public function __construct(private readonly TallyPostService $tally)
    {
    }

    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:tally.view', only: ['settings', 'logs']),
            new Middleware('permission:tally.edit', only: ['updateSettings', 'saveGstIrn']),
            new Middleware('permission:tally.post', only: ['exportDocument', 'debitNote']),
        ];
    }

    public function settings(): View
    {
        return view('finance.tally.settings', [
            'settings' => TallySetting::current(),
        ]);
    }

    public function updateSettings(UpdateTallySettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_enabled'] = $request->boolean('is_enabled');

        TallySetting::current()->update($data);

        return redirect()
            ->route('finance.tally.settings')
            ->with('success', 'Tally settings saved. Ledger names must match the company file in Tally.');
    }

    public function logs(): View
    {
        $logs = TallyPostLog::query()
            ->with('poster:id,name')
            ->latest('id')
            ->paginate(30);

        return view('finance.tally.logs', compact('logs'));
    }

    public function saveGstIrn(Request $request, ExportDocument $document): RedirectResponse
    {
        $validated = $request->validate([
            'gst_irn'     => ['nullable', 'string', 'max:64'],
            'gst_ack_no'  => ['nullable', 'string', 'max:64'],
            'gst_ack_date' => ['nullable', 'date'],
        ]);

        $document->update($validated);

        return redirect()
            ->route('export.documents.show', $document)
            ->with('success', 'GST IRN saved. Generate Tally XML after this so the IRN goes in the voucher narration.');
    }

    public function exportDocument(Request $request, ExportDocument $document): StreamedResponse|RedirectResponse
    {
        try {
            $prepared = $this->tally->prepareSales($document);
        } catch (RuntimeException $e) {
            return back()->with('warning', $e->getMessage());
        }

        if ($request->input('mode') === 'post') {
            try {
                $this->tally->postToGateway($prepared['log']);
            } catch (RuntimeException $e) {
                return back()->with('warning', $e->getMessage());
            }

            return back()->with('success', 'Sales voucher posted to Tally for invoice '.($document->invoice_no ?: $document->doc_num).'.');
        }

        $filename = 'tally-sales-'.$this->safeFilename($document->invoice_no ?: $document->doc_num).'.xml';

        return $this->downloadXml($filename, $prepared['xml']);
    }

    public function debitNote(Request $request, DebitNote $debitNote): StreamedResponse|RedirectResponse
    {
        try {
            $prepared = $this->tally->prepareDebitNote($debitNote);
        } catch (RuntimeException $e) {
            return back()->with('warning', $e->getMessage());
        }

        if ($request->input('mode') === 'post') {
            try {
                $this->tally->postToGateway($prepared['log']);
            } catch (RuntimeException $e) {
                return back()->with('warning', $e->getMessage());
            }

            return back()->with('success', 'Debit note '.$debitNote->debit_note_num.' posted to Tally.');
        }

        return $this->downloadXml('tally-dn-'.$this->safeFilename($debitNote->debit_note_num).'.xml', $prepared['xml']);
    }

    private function safeFilename(?string $name): string
    {
        return preg_replace('/[\/\\\\]+/', '-', (string) $name) ?: 'voucher';
    }

    private function downloadXml(string $filename, string $xml): StreamedResponse
    {
        return Response::streamDownload(function () use ($xml) {
            echo $xml;
        }, $filename, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
