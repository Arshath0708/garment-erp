<?php

namespace App\Http\Controllers\Communication;

use App\Http\Controllers\Controller;
use App\Http\Requests\Communication\UpdateWhatsappSettingsRequest;
use App\Models\PurchaseOrder;
use App\Models\TimeAndActionStep;
use App\Models\WhatsappMessageLog;
use App\Models\WhatsappSetting;
use App\Services\Communication\WhatsappService;
use App\Support\WhatsappPhone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use RuntimeException;

class WhatsappController extends Controller implements HasMiddleware
{
    public function __construct(private readonly WhatsappService $whatsapp) {}

    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:whatsapp.view', only: ['settings', 'logs']),
            new Middleware('permission:whatsapp.edit', only: ['updateSettings']),
            new Middleware('permission:whatsapp.send', only: ['purchaseOrder', 'tnaStep']),
        ];
    }

    public function settings(): View
    {
        return view('communication.whatsapp.settings', [
            'settings' => WhatsappSetting::current(),
        ]);
    }

    public function updateSettings(UpdateWhatsappSettingsRequest $request): RedirectResponse
    {
        $settings = WhatsappSetting::current();
        $settings->update([
            'is_enabled' => $request->boolean('is_enabled'),
            'phone_number_id' => $request->input('phone_number_id'),
            'graph_version' => $request->input('graph_version'),
            'country_code' => preg_replace('/\D+/', '', (string) $request->input('country_code')) ?: '91',
        ]);
        $settings->storeToken($request->input('access_token'));

        return redirect()
            ->route('whatsapp.settings')
            ->with('success', 'WhatsApp settings saved. Cloud API send is optional — Open WhatsApp still works without a token.');
    }

    public function logs(): View
    {
        $logs = WhatsappMessageLog::query()
            ->with('sender:id,name')
            ->latest('id')
            ->paginate(30);

        return view('communication.whatsapp.logs', compact('logs'));
    }

    public function purchaseOrder(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $settings = WhatsappSetting::current();
        $digits = WhatsappPhone::digits($request->input('mobile'), (string) $settings->country_code)
            ?: $this->whatsapp->supplierDigits($purchaseOrder);

        if (! $digits) {
            return back()->with('warning', 'Add a mobile number on the supplier’s primary contact, or type one here.');
        }

        $body = $this->whatsapp->purchaseOrderMessage($purchaseOrder);

        return $this->dispatch($request, WhatsappMessageLog::SOURCE_PO, $purchaseOrder->id, $digits, $body);
    }

    public function tnaStep(Request $request, TimeAndActionStep $step): RedirectResponse
    {
        $settings = WhatsappSetting::current();
        $digits = WhatsappPhone::digits($request->input('mobile'), (string) $settings->country_code)
            ?: $this->whatsapp->buyerDigits($step);

        if (! $digits) {
            return back()->with('warning', 'Add a mobile number on the buyer, or type one here.');
        }

        $body = $this->whatsapp->lateStepMessage($step);

        return $this->dispatch($request, WhatsappMessageLog::SOURCE_TNA, $step->id, $digits, $body);
    }

    private function dispatch(Request $request, string $sourceType, int $sourceId, string $digits, string $body): RedirectResponse
    {
        if ($request->input('mode') === 'api') {
            try {
                $this->whatsapp->sendApi($digits, $body, $sourceType, $sourceId);
            } catch (RuntimeException $e) {
                return back()->with('warning', $e->getMessage());
            }

            return back()->with('success', 'WhatsApp message sent to '.$digits.'.');
        }

        $this->whatsapp->logOpened($sourceType, $sourceId, $digits, $body);

        return redirect()->away($this->whatsapp->chatUrl($digits, $body));
    }
}
