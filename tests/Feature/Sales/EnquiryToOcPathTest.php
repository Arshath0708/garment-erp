<?php

namespace Tests\Feature\Sales;

use App\Models\Buyer;
use App\Models\Category;
use App\Models\Currency;
use App\Models\DocumentFormat;
use App\Models\Inquiry;
use App\Models\OrderConfirmation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EnquiryToOcPathTest extends TestCase
{
    use RefreshDatabase;

    public function test_converting_inquiry_copies_shipment_date_and_remarks(): void
    {
        foreach (['order-confirmation.create'] as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $user = User::factory()->create();
        $user->givePermissionTo(['order-confirmation.create']);

        $buyer = Buyer::forceCreate([
            'display_code' => 'BUY09',
            'company_name' => 'Path Buyer',
            'status'       => 'active',
        ]);
        $category = new Category(['name' => 'Knits', 'status' => 'active']);
        $category->code = 'CAT009';
        $category->save();
        $format = DocumentFormat::create(['name' => 'Path Format', 'status' => 'active']);
        $currency = new Currency(['name' => 'US Dollar', 'symbol' => '$', 'status' => 'active']);
        $currency->iso_code = 'USD';
        $currency->save();

        $inquiry = Inquiry::forceCreate([
            'inquiry_no'              => 'INQ-PATH-1',
            'financial_year'          => '2026-27',
            'buyer_id'                => $buyer->id,
            'category_id'             => $category->id,
            'document_format_id'      => $format->id,
            'currency_id'             => $currency->id,
            'inquiry_date'            => now()->toDateString(),
            'buyer_ref'               => 'PO-7788',
            'expected_shipment_date'  => '2026-11-15',
            'remarks'                 => 'Ship via Nhava Sheva',
            'status'                  => 'confirmed',
        ]);
        $inquiry->items()->create([
            'sort_order'  => 0,
            'design_no'   => 'D-PATH',
            'description' => 'Tee',
            'unit'        => 'pcs',
            'qty'         => 50,
            'status'      => 'confirmed',
        ]);

        $this->actingAs($user)
            ->post(route('sales.inquiries.convert-to-oc', $inquiry))
            ->assertRedirect();

        $oc = OrderConfirmation::query()->first();
        $this->assertNotNull($oc);
        $this->assertSame('2026-11-15', substr((string) $oc->shipment_date, 0, 10));
        $this->assertSame('Ship via Nhava Sheva', $oc->remarks);
        $this->assertSame('PO-7788', $oc->buyer_ref);
        $this->assertSame($inquiry->id, $oc->source_inquiry_id);
    }
}
