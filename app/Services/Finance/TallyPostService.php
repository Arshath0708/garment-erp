<?php

namespace App\Services\Finance;

use App\Models\DebitNote;
use App\Models\ExportDocument;
use App\Models\TallyPostLog;
use App\Models\TallySetting;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TallyPostService
{
    public function __construct(private readonly TallyXmlService $xml)
    {
    }

    /**
     * @return array{xml: string, log: TallyPostLog}
     */
    public function prepareSales(ExportDocument $document): array
    {
        if (! filled($document->invoice_no) && ! filled($document->doc_num)) {
            throw new RuntimeException('This export document has no invoice or document number to send to Tally.');
        }

        $settings = TallySetting::current();
        $xml = $this->xml->salesVoucher($document, $settings);

        return ['xml' => $xml, 'log' => $this->remember(
            TallyPostLog::SOURCE_EXPORT,
            $document->id,
            $settings->sales_voucher_type,
            $document->invoice_no ?: $document->doc_num,
            $xml,
            'downloaded',
        )];
    }

    /**
     * @return array{xml: string, log: TallyPostLog}
     */
    public function prepareDebitNote(DebitNote $note): array
    {
        if ($note->status !== 'issued') {
            throw new RuntimeException('Only issued debit notes can go to Tally.');
        }

        $settings = TallySetting::current();
        $xml = $this->xml->debitNoteVoucher($note, $settings);

        return ['xml' => $xml, 'log' => $this->remember(
            TallyPostLog::SOURCE_DEBIT,
            $note->id,
            $settings->debit_note_voucher_type,
            $note->debit_note_num,
            $xml,
            'downloaded',
        )];
    }

    public function postToGateway(TallyPostLog $log): TallyPostLog
    {
        $settings = TallySetting::current();
        if (! $settings->is_enabled) {
            throw new RuntimeException('Turn on Tally posting in Finance → Tally before sending. You can still download the XML.');
        }

        try {
            $response = Http::timeout(8)
                ->withBody($log->request_xml, 'text/xml; charset=utf-8')
                ->post($settings->host_url);
        } catch (\Throwable $e) {
            $log->update([
                'status'         => 'failed',
                'error_message'  => $e->getMessage(),
                'response_body'  => null,
                'posted_by'      => auth()->id(),
                'posted_at'      => now(),
            ]);

            throw new RuntimeException('Tally did not respond at '.$settings->host_url.'. Download the XML and import it in Tally, or check that Tally is running with XML on that port.');
        }

        $body = $response->body();
        $ok = $response->successful() && ! $this->tallyRejected($body);

        $log->update([
            'status'        => $ok ? 'posted' : 'failed',
            'response_body' => $body,
            'error_message' => $ok ? null : ($this->tallyError($body) ?: 'Tally rejected the voucher.'),
            'posted_by'     => auth()->id(),
            'posted_at'     => now(),
        ]);

        if (! $ok) {
            throw new RuntimeException($log->error_message);
        }

        return $log->fresh();
    }

    private function remember(
        string $sourceType,
        int $sourceId,
        string $voucherType,
        ?string $voucherNumber,
        string $xml,
        string $status,
    ): TallyPostLog {
        return TallyPostLog::query()->updateOrCreate(
            [
                'source_type'  => $sourceType,
                'source_id'    => $sourceId,
                'voucher_type' => $voucherType,
            ],
            [
                'voucher_number' => $voucherNumber,
                'status'         => $status,
                'request_xml'    => $xml,
                'response_body'  => null,
                'error_message'  => null,
                'posted_by'      => auth()->id(),
                'posted_at'      => now(),
            ]
        );
    }

    private function tallyRejected(string $body): bool
    {
        return str_contains($body, 'LINEERROR')
            || str_contains($body, 'Unknown Request')
            || str_contains($body, '<ERRORS>');
    }

    private function tallyError(string $body): ?string
    {
        if (preg_match('/<LINEERROR>([^<]+)<\/LINEERROR>/', $body, $m)) {
            return trim($m[1]);
        }

        return null;
    }
}
