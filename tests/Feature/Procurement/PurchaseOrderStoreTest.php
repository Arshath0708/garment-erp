<?php

namespace Tests\Feature\Procurement;

use App\Models\Buyer;
use App\Models\Category;
use App\Models\Currency;
use App\Models\DocumentFormat;
use App\Models\OrderConfirmation;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Support\FinancialYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseOrderStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_draft_assigns_po_number_and_does_not_500(): void
    {
        $this->artisan('permission:sync --roles');

        $admin = User::factory()->create();
        $admin->assignRole(Role::firstOrCreate(['name' => 'Super Admin']));

        $supplier = Supplier::create([
            'display_code' => 'SPU1',
            'company_name' => 'sri',
            'party_type'   => 'supplier',
            'status'       => 'active',
        ]);

        $buyer = Buyer::forceCreate([
            'display_code' => 'BUY01',
            'company_name' => 'guru traders',
            'status'       => 'active',
        ]);

        $category = new Category(['name' => 'tshirt', 'status' => 'active']);
        $category->code = 'CAT001';
        $category->save();

        $format = DocumentFormat::create(['name' => 'Standard', 'status' => 'active']);

        $currency = new Currency(['name' => 'INR', 'symbol' => '₹', 'status' => 'active']);
        $currency->iso_code = 'INR';
        $currency->save();

        $oc = new OrderConfirmation([
            'buyer_id'           => $buyer->id,
            'category_id'        => $category->id,
            'document_format_id' => $format->id,
            'currency_id'        => $currency->id,
            'oc_date'            => now()->toDateString(),
            'status'             => 'confirmed',
        ]);
        $oc->oc_num = 'GT/BUY01/001/'.FinancialYear::current();
        $oc->financial_year = FinancialYear::current();
        $oc->save();

        $this->actingAs($admin)
            ->post(route('procurement.purchase-orders.store'), [
                'order_confirmation_id' => $oc->id,
                'supplier_id'           => $supplier->id,
                'po_date'               => now()->toDateString(),
                'dispatch_date'         => now()->toDateString(),
                'delivery_details'      => 'Factory delivery',
                'packing_details'       => 'Roll packing',
                'status'                => 'draft',
                'items'                 => [
                    [
                        'design_no'  => 'tshirt r',
                        'product_id' => null,
                        'unit'       => 'MTR',
                        'cost_price' => 50,
                        'colours'    => [
                            [
                                'colour' => 'White',
                                'sizes'  => [
                                    ['size' => 'M', 'qty' => 4578],
                                ],
                            ],
                        ],
                    ],
                ],
                'timeline' => [
                    ['date' => now()->addDay()->toDateString(), 'note' => 'Expected', 'qty' => 4578],
                ],
            ])
            ->assertRedirect(route('procurement.purchase-orders.index'))
            ->assertSessionHas('success');

        $po = PurchaseOrder::query()->latest('id')->first();
        $this->assertNotNull($po);
        $this->assertNotSame('', (string) $po->po_num);
        $this->assertStringStartsWith('GT/PO/', $po->po_num);
        $this->assertSame(FinancialYear::current(), $po->financial_year);
        $this->assertSame('draft', $po->status);
        $this->assertSame(4578, (int) $po->items()->first()?->qty);
    }
}
