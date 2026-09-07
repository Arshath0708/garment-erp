<?php

namespace Tests\Feature\Communication;

use App\Models\Buyer;
use App\Models\Category;
use App\Models\Currency;
use App\Models\DocumentFormat;
use App\Models\GarmentStyle;
use App\Models\OrderConfirmation;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\TimeAndActionStep;
use App\Models\User;
use App\Models\WhatsappMessageLog;
use App\Models\WhatsappSetting;
use App\Services\Manufacturing\WorkOrderService;
use App\Support\FinancialYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class WhatsappAlertTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['whatsapp.view', 'whatsapp.edit', 'whatsapp.send', 'purchase-order.view', 'work-order.view', 'work-order.create', 'work-order.approve'] as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $this->user = User::factory()->create();
        $this->user->givePermissionTo(['whatsapp.view', 'whatsapp.edit', 'whatsapp.send', 'purchase-order.view', 'work-order.view', 'work-order.create', 'work-order.approve']);
    }

    public function test_settings_save_encrypts_the_token_and_blank_save_keeps_it(): void
    {
        $this->actingAs($this->user)
            ->put(route('whatsapp.settings.update'), [
                'is_enabled' => '1',
                'phone_number_id' => '123456',
                'access_token' => 'plain-secret-token',
                'graph_version' => 'v21.0',
                'country_code' => '91',
            ])
            ->assertRedirect(route('whatsapp.settings'));

        $settings = WhatsappSetting::current();
        $this->assertTrue($settings->is_enabled);
        $this->assertSame('123456', $settings->phone_number_id);
        $this->assertNotSame('plain-secret-token', $settings->access_token_encrypted);
        $this->assertSame('plain-secret-token', Crypt::decryptString($settings->access_token_encrypted));

        $this->actingAs($this->user)
            ->get(route('whatsapp.settings'))
            ->assertOk()
            ->assertDontSee('plain-secret-token');

        $this->actingAs($this->user)
            ->put(route('whatsapp.settings.update'), [
                'phone_number_id' => '123456',
                'graph_version' => 'v21.0',
                'country_code' => '91',
            ])
            ->assertRedirect(route('whatsapp.settings'));

        $this->assertSame('plain-secret-token', WhatsappSetting::current()->accessToken());
    }

    public function test_open_whatsapp_from_a_purchase_order_logs_and_redirects_to_wa_me(): void
    {
        $po = $this->makePurchaseOrder('9876543210');

        $response = $this->actingAs($this->user)
            ->post(route('whatsapp.purchase-orders', $po), [
                'mode' => 'wa_me',
            ]);

        $response->assertRedirect();
        $this->assertStringStartsWith('https://wa.me/919876543210?text=', (string) $response->headers->get('Location'));

        $this->assertDatabaseHas('whatsapp_message_logs', [
            'source_type' => WhatsappMessageLog::SOURCE_PO,
            'source_id' => $po->id,
            'to_digits' => '919876543210',
            'channel' => 'wa_me',
            'status' => 'opened',
            'sent_by' => $this->user->id,
        ]);
    }

    public function test_cloud_api_send_posts_to_graph_and_logs_sent(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.test']]], 200),
        ]);

        $this->enableApi();
        $po = $this->makePurchaseOrder('9876543210');

        $this->actingAs($this->user)
            ->from(route('procurement.purchase-orders.show', $po))
            ->post(route('whatsapp.purchase-orders', $po), [
                'mode' => 'api',
            ])
            ->assertRedirect(route('procurement.purchase-orders.show', $po))
            ->assertSessionHas('success');

        Http::assertSent(function ($request) {
            $data = $request->data();

            return str_contains($request->url(), 'graph.facebook.com/v21.0/555111/messages')
                && ($data['to'] ?? null) === '919876543210'
                && ($data['type'] ?? null) === 'text'
                && str_contains((string) ($data['text']['body'] ?? ''), 'Purchase order');
        });

        $this->assertDatabaseHas('whatsapp_message_logs', [
            'source_type' => WhatsappMessageLog::SOURCE_PO,
            'source_id' => $po->id,
            'channel' => 'api',
            'status' => 'sent',
        ]);
    }

    public function test_cloud_api_failure_is_logged_and_shown(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['error' => ['message' => 'Invalid OAuth token', 'code' => 190]], 401),
        ]);

        $this->enableApi();
        $po = $this->makePurchaseOrder('9876543210');

        $this->actingAs($this->user)
            ->from(route('procurement.purchase-orders.show', $po))
            ->post(route('whatsapp.purchase-orders', $po), [
                'mode' => 'api',
            ])
            ->assertRedirect(route('procurement.purchase-orders.show', $po))
            ->assertSessionHas('warning', 'Invalid OAuth token');

        $this->assertDatabaseHas('whatsapp_message_logs', [
            'source_id' => $po->id,
            'channel' => 'api',
            'status' => 'failed',
        ]);
    }

    public function test_api_send_is_blocked_until_cloud_api_is_turned_on(): void
    {
        Http::fake();
        $po = $this->makePurchaseOrder('9876543210');

        $this->actingAs($this->user)
            ->from(route('procurement.purchase-orders.show', $po))
            ->post(route('whatsapp.purchase-orders', $po), [
                'mode' => 'api',
            ])
            ->assertRedirect(route('procurement.purchase-orders.show', $po))
            ->assertSessionHas('warning');

        $this->assertDatabaseCount('whatsapp_message_logs', 0);
        Http::assertNothingSent();
    }

    public function test_missing_mobile_warns_without_logging(): void
    {
        $po = $this->makePurchaseOrder(null);

        $this->actingAs($this->user)
            ->from(route('procurement.purchase-orders.show', $po))
            ->post(route('whatsapp.purchase-orders', $po), [
                'mode' => 'wa_me',
            ])
            ->assertRedirect(route('procurement.purchase-orders.show', $po))
            ->assertSessionHas('warning');

        $this->assertDatabaseCount('whatsapp_message_logs', 0);
    }

    public function test_late_tna_step_opens_whatsapp_to_the_buyer(): void
    {
        $step = $this->lateStep('9988776655');

        $response = $this->actingAs($this->user)
            ->post(route('whatsapp.tna-steps', $step), [
                'mode' => 'wa_me',
            ]);

        $response->assertRedirect();
        $this->assertStringStartsWith('https://wa.me/919988776655?text=', (string) $response->headers->get('Location'));
        $this->assertStringContainsString('Time', urldecode((string) $response->headers->get('Location')));

        $this->assertDatabaseHas('whatsapp_message_logs', [
            'source_type' => WhatsappMessageLog::SOURCE_TNA,
            'source_id' => $step->id,
            'to_digits' => '919988776655',
            'status' => 'opened',
        ]);
    }

    public function test_guest_and_user_without_permission_are_blocked(): void
    {
        $this->get(route('whatsapp.settings'))->assertRedirect(route('login'));

        $plain = User::factory()->create();
        $this->actingAs($plain)->get(route('whatsapp.settings'))->assertForbidden();
        $this->actingAs($plain)->get(route('whatsapp.logs'))->assertForbidden();

        $po = $this->makePurchaseOrder('9876543210');
        $this->actingAs($plain)
            ->post(route('whatsapp.purchase-orders', $po), ['mode' => 'wa_me'])
            ->assertForbidden();
    }

    public function test_purchase_order_show_offers_whatsapp_when_allowed(): void
    {
        $po = $this->makePurchaseOrder('9876543210');

        $this->actingAs($this->user)
            ->get(route('procurement.purchase-orders.show', $po))
            ->assertOk()
            ->assertSee('Open WhatsApp', false)
            ->assertSee('919876543210', false);
    }

    private function enableApi(): void
    {
        $settings = WhatsappSetting::current();
        $settings->update([
            'is_enabled' => true,
            'phone_number_id' => '555111',
            'graph_version' => 'v21.0',
            'country_code' => '91',
        ]);
        $settings->storeToken('test-token');
    }

    private int $seq = 0;

    private function tag(): string
    {
        $this->seq++;

        return str_pad((string) $this->seq, 4, '0', STR_PAD_LEFT);
    }

    private function makePurchaseOrder(?string $mobile): PurchaseOrder
    {
        $tag = $this->tag();

        $supplier = Supplier::create([
            'display_code' => 'S'.$tag,
            'company_name' => 'WA Fabric',
            'party_type' => 'supplier',
            'status' => 'active',
        ]);

        if ($mobile) {
            $supplier->contacts()->create([
                'name' => 'Ramesh',
                'mobile' => $mobile,
                'is_primary' => true,
            ]);
        }

        $buyer = Buyer::forceCreate([
            'display_code' => 'B'.$tag,
            'company_name' => 'WA Buyer',
            'status' => 'active',
        ]);

        $category = new Category(['name' => 'tshirt', 'status' => 'active']);
        $category->code = 'C'.$tag;
        $category->save();

        $format = DocumentFormat::query()->firstOrCreate(
            ['name' => 'Standard'],
            ['status' => 'active']
        );

        $currency = Currency::query()->where('iso_code', 'INR')->first();
        if (! $currency) {
            $currency = new Currency(['name' => 'INR', 'symbol' => '₹', 'status' => 'active']);
            $currency->iso_code = 'INR';
            $currency->save();
        }

        $oc = new OrderConfirmation([
            'buyer_id' => $buyer->id,
            'category_id' => $category->id,
            'document_format_id' => $format->id,
            'currency_id' => $currency->id,
            'oc_date' => now()->toDateString(),
            'status' => 'confirmed',
        ]);
        $oc->oc_num = 'GT/'.$tag.'/001/'.FinancialYear::current();
        $oc->financial_year = FinancialYear::current();
        $oc->save();

        $po = new PurchaseOrder([
            'order_confirmation_id' => $oc->id,
            'supplier_id' => $supplier->id,
            'po_date' => now()->toDateString(),
            'status' => 'raised',
        ]);
        $po->po_num = 'GT/PO/'.$tag.'/'.FinancialYear::current();
        $po->financial_year = FinancialYear::current();
        $po->save();

        $po->items()->create([
            'design_no' => 'Tee',
            'unit' => 'PCS',
            'cost_price' => 50,
            'qty' => 10,
            'amount' => 500,
        ]);

        return $po->fresh(['supplier.primaryContact', 'items']);
    }

    private function lateStep(string $mobile): TimeAndActionStep
    {
        $tag = $this->tag();

        $buyer = Buyer::forceCreate([
            'display_code' => 'T'.$tag,
            'company_name' => 'Late Buyer',
            'mobile' => $mobile,
            'status' => 'active',
        ]);

        $style = GarmentStyle::create([
            'style_number' => 'ST'.$tag,
            'name' => 'Late Tee',
            'status' => 'Active',
            'target_qty' => 100,
            'buyer_id' => $buyer->id,
        ]);
        $this->approveStyleCosting($style);

        $workOrder = app(WorkOrderService::class)->create([
            'wo_date' => now()->toDateString(),
            'garment_style_id' => $style->id,
            'total_qty' => 100,
            'target_date' => now()->subDays(5)->toDateString(),
        ]);

        app(WorkOrderService::class)->release($workOrder);

        $step = $workOrder->fresh('steps')->step('dispatch');
        $this->assertNotNull($step);
        $this->assertTrue($step->isLate());

        return $step;
    }
}
