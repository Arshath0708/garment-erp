<?php

namespace Tests\Feature\Inventory;

use App\Models\Buyer;
use App\Models\Category;
use App\Models\Currency;
use App\Models\DocumentFormat;
use App\Models\InwardEntry;
use App\Models\OrderConfirmation;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockLot;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LotsGodownsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('permission:sync --roles');

        $this->admin = User::factory()->create();
        $this->admin->assignRole(Role::firstOrCreate(['name' => 'Super Admin']));
    }

    public function test_default_godowns_are_seeded(): void
    {
        $this->assertDatabaseHas('warehouses', ['code' => 'MAIN']);
        $this->assertDatabaseHas('warehouses', ['code' => 'FG']);
    }

    public function test_admin_can_create_extra_godown(): void
    {
        $this->actingAs($this->admin)
            ->post(route('inventory.warehouses.store'), [
                'code' => 'roll-a',
                'name' => 'Roll Store A',
                'kind' => 'fabric',
                'is_active' => '1',
            ])
            ->assertRedirect(route('inventory.warehouses.index'));

        $this->assertDatabaseHas('warehouses', [
            'code' => 'ROLL-A',
            'name' => 'Roll Store A',
        ]);
    }

    public function test_stores_receive_creates_lot_in_godown(): void
    {
        $supplier = Supplier::create([
            'display_code' => 'SUPLOT',
            'company_name' => 'Lot Test Supplier',
            'party_type'   => 'supplier',
            'status'       => 'active',
        ]);

        $buyer = Buyer::forceCreate([
            'display_code' => 'BUYLOT',
            'company_name' => 'Lot Test Buyer',
            'status'       => 'active',
        ]);

        $category = new Category(['name' => 'Woven', 'status' => 'active']);
        $category->code = 'CATLOT';
        $category->save();

        $format = DocumentFormat::create(['name' => 'Std', 'status' => 'active']);

        $currency = new Currency(['name' => 'USD', 'symbol' => '$', 'status' => 'active']);
        $currency->iso_code = 'USD';
        $currency->save();

        $oc = new OrderConfirmation([
            'buyer_id'           => $buyer->id,
            'category_id'        => $category->id,
            'document_format_id' => $format->id,
            'currency_id'        => $currency->id,
            'oc_date'            => now()->toDateString(),
            'status'             => 'confirmed',
        ]);
        $oc->oc_num = 'GT/OC/LOT/26-27';
        $oc->financial_year = '26-27';
        $oc->save();

        $po = new PurchaseOrder([
            'order_confirmation_id' => $oc->id,
            'supplier_id'           => $supplier->id,
            'po_date'               => now()->toDateString(),
            'status'                => 'raised',
        ]);
        $po->po_num = 'GT/PO/LOT/26-27';
        $po->financial_year = '26-27';
        $po->save();

        $poItem = $po->items()->create([
            'sort_order'  => 0,
            'description' => 'Roll Fabric',
            'unit'        => 'mtr',
            'cost_price'  => 10,
            'qty'         => 50,
            'amount'      => 500,
            'product_id'  => null,
        ]);

        $product = Product::create([
            'category_id'     => $category->id,
            'item_group_code' => 'LOT'.substr(uniqid(), -6),
            'name'            => 'Roll Fabric',
            'status'          => 'active',
            'item_kind'       => 'fabric',
            'qty_on_hand'     => 5,
            'unit_po'         => 'mtr',
        ]);

        $inward = new InwardEntry([
            'financial_year'    => '26-27',
            'inward_date'       => now()->toDateString(),
            'purchase_order_id' => $po->id,
            'supplier_id'       => $supplier->id,
            'status'            => 'approved',
        ]);
        $inward->inward_no = 'GT/INW/099/26-27';
        $inward->save();

        $item = $inward->items()->create([
            'purchase_order_item_id' => $poItem->id,
            'product_id'             => $product->id,
            'ordered_qty'            => 50,
            'received_qty'           => 20,
            'passed_qty'             => 18,
            'rejected_qty'           => 2,
        ]);

        $warehouse = Warehouse::where('code', 'MAIN')->firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('procurement.inward-entries.stores-receive', $inward), [
                'warehouse_id' => $warehouse->id,
                'lot_numbers' => [
                    $item->id => 'ROLL-42',
                ],
            ])
            ->assertRedirect(route('procurement.inward-entries.show', $inward));

        $this->assertNotNull($inward->fresh()->stores_received_at);
        $this->assertEquals('23.000', (string) $product->fresh()->qty_on_hand);

        $lot = StockLot::query()->where('product_id', $product->id)->first();
        $this->assertNotNull($lot);
        $this->assertEquals('ROLL-42', $lot->lot_no);
        $this->assertEquals($warehouse->id, $lot->warehouse_id);
        $this->assertEquals('18.000', (string) $lot->qty_on_hand);

        $this->actingAs($this->admin)
            ->get(route('inventory.lots'))
            ->assertOk()
            ->assertSee('ROLL-42')
            ->assertSee('Roll Fabric');
    }
}
