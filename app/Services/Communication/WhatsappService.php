<?php

namespace App\Services\Communication;

use App\Models\PurchaseOrder;
use App\Models\TimeAndActionStep;
use App\Models\WhatsappMessageLog;
use App\Models\WhatsappSetting;
use App\Support\WhatsappPhone;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WhatsappService
{
    public function purchaseOrderMessage(PurchaseOrder $po): string
    {
        $po->loadMissing(['supplier', 'items']);
        $amount = number_format($po->totalAmount(), 2);
        $date = $po->po_date?->format('d M Y') ?: now()->format('d M Y');
        $url = route('procurement.purchase-orders.show', $po);

        return "Purchase order {$po->po_num} dated {$date}. Amount {$amount}. Please confirm. {$url}";
    }

    public function lateStepMessage(TimeAndActionStep $step): string
    {
        $step->loadMissing(['workOrder.garmentStyle', 'workOrder.buyer']);
        $wo = $step->workOrder?->wo_num ?: 'WO';
        $style = $step->workOrder?->garmentStyle?->style_number ?: 'style';
        $days = $step->daysLate();

        return "Time & Action: {$step->label} on {$wo} ({$style}) is {$days} day(s) late. Planned {$step->planned_date?->format('d M Y')}.";
    }

    public function supplierDigits(PurchaseOrder $po): ?string
    {
        $po->loadMissing('supplier.primaryContact');
        $settings = WhatsappSetting::current();

        return WhatsappPhone::digits(
            $po->supplier?->primaryContact?->mobile,
            (string) $settings->country_code
        );
    }

    public function buyerDigits(TimeAndActionStep $step): ?string
    {
        $step->loadMissing('workOrder.buyer');
        $settings = WhatsappSetting::current();

        return WhatsappPhone::digits(
            $step->workOrder?->buyer?->mobile,
            (string) $settings->country_code
        );
    }

    public function chatUrl(string $digits, string $text): string
    {
        return WhatsappPhone::chatUrl($digits, $text);
    }

    public function logOpened(string $sourceType, int $sourceId, string $digits, string $body): WhatsappMessageLog
    {
        return WhatsappMessageLog::query()->create([
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'to_digits' => $digits,
            'body' => $body,
            'channel' => 'wa_me',
            'status' => 'opened',
            'sent_by' => auth()->id(),
            'sent_at' => now(),
        ]);
    }

    public function sendApi(string $digits, string $body, string $sourceType, int $sourceId): WhatsappMessageLog
    {
        $settings = WhatsappSetting::current();
        if (! $settings->is_enabled) {
            throw new RuntimeException('Turn on WhatsApp Cloud API in WhatsApp settings, or use Open WhatsApp (no API needed).');
        }

        $token = $settings->accessToken();
        $phoneId = $settings->phone_number_id;
        if (! filled($token) || ! filled($phoneId)) {
            throw new RuntimeException('Save the WhatsApp phone number ID and access token first.');
        }

        $version = $settings->graph_version ?: 'v21.0';
        $url = 'https://graph.facebook.com/'.$version.'/'.$phoneId.'/messages';

        try {
            $response = Http::timeout(12)
                ->withToken($token)
                ->acceptJson()
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'to' => $digits,
                    'type' => 'text',
                    'text' => ['body' => $body, 'preview_url' => true],
                ]);
        } catch (\Throwable $e) {
            $log = $this->writeLog($sourceType, $sourceId, $digits, $body, 'api', 'failed', $e->getMessage());
            throw new RuntimeException('WhatsApp API did not respond: '.$e->getMessage(), previous: $e);
        }

        if (! $response->successful()) {
            $message = (string) ($response->json('error.message') ?: $response->body());
            $this->writeLog($sourceType, $sourceId, $digits, $body, 'api', 'failed', $message);
            throw new RuntimeException($message ?: 'WhatsApp API rejected the message.');
        }

        return $this->writeLog($sourceType, $sourceId, $digits, $body, 'api', 'sent', null);
    }

    private function writeLog(
        string $sourceType,
        int $sourceId,
        string $digits,
        string $body,
        string $channel,
        string $status,
        ?string $error,
    ): WhatsappMessageLog {
        return WhatsappMessageLog::query()->create([
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'to_digits' => $digits,
            'body' => $body,
            'channel' => $channel,
            'status' => $status,
            'error_message' => $error,
            'sent_by' => auth()->id(),
            'sent_at' => now(),
        ]);
    }
}
