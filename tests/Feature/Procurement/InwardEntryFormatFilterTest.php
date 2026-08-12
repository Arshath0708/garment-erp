<?php

namespace Tests\Feature\Procurement;

use App\Models\Buyer;
use App\Models\Category;
use App\Models\Currency;
use App\Models\DocumentFormat;
use App\Models\OrderConfirmation;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InwardEntryFormatFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private PurchaseOrder $po;
    private Category $ocCategory;
    private Category $otherCategory;
    private Product $matchingProduct;
    private Product $formatLinkedProduct;
    private Product $excludedProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('permission:sync --roles');

        $this->admin = User::factory()->create();
        $this->admin->assignRole(Role::firstOrCreate(['name' => 'Super Admin']));
        $this->admin->givePermissionTo(['inward-entry.view', 'inward-entry.create']);

        $supplier = Supplier::create([
            'display_code' => 'SUP201',
            'company_name' => 'Filter Test Supplier',
            'party_type' => 'supplier',
            'status' => 'active',
        ]);

        $buyer = Buyer::forceCreate([
            'display_code' => 'BUY201',
            'company_name' => 'Filter Test Buyer',
            'status' => 'active',
        ]);

        $this->ocCategory = new Category(['name' => 'OC Category', 'status' => 'active']);
        $this->ocCategory->code = 'CAT201';
        $this->ocCategory->save();

        $this->otherCategory = new Category(['name' => 'Other Category', 'status' => 'active']);
        $this->otherCategory->code = 'CAT202';
        $this->otherCategory->save();

        $formatLinkedCategory = new Category(['name' => 'Format Linked', 'status' => 'active']);
        $formatLinkedCategory->code = 'CAT203';
        $formatLinkedCategory->save();

        $format = DocumentFormat::create([
            'name' => 'Filter Format',
            'status' => 'active',
        ]);
        $format->categories()->attach($formatLinkedCategory->id);

        $currency = Currency::create([
            'iso_code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'status' => 'active',
        ]);

        $this->matchingProduct = Product::create([
            'category_id' => $this->ocCategory->id,
            'item_group_code' => 'PRD201',
            'name' => 'Matching OC Product',
            'status' => 'active',
        ]);

        $this->formatLinkedProduct = Product::create([
            'category_id' => $formatLinkedCategory->id,
            'item_group_code' => 'PRD202',
            'name' => 'Format Category Product',
            'status' => 'active',
        ]);

        $this->excludedProduct = Product::create([
            'category_id' => $this->otherCategory->id,
            'item_group_code' => 'PRD203',
            'name' => 'Excluded Product',
            'status' => 'active',
        ]);

        $oc = new OrderConfirmation([
            'buyer_id' => $buyer->id,
            'category_id' => $this->ocCategory->id,
            'document_format_id' => $format->id,
            'currency_id' => $currency->id,
            'oc_date' => now()->toDateString(),
            'status' => 'confirmed',
        ]);
        $oc->oc_num = 'GT/OC/201/26-27';
        $oc->financial_year = '26-27';
        $oc->save();

        $this->po = new PurchaseOrder([
            'order_confirmation_id' => $oc->id,
            'supplier_id' => $supplier->id,
            'po_date' => now()->toDateString(),
            'status' => 'raised',
        ]);
        $this->po->po_num = 'GT/PO/201/26-27';
        $this->po->financial_year = '26-27';
        $this->po->save();

        $this->po->items()->create([
            'sort_order' => 0,
            'product_id' => $this->matchingProduct->id,
            'description' => 'Match line',
            'unit' => 'pcs',
            'cost_price' => 10,
            'qty' => 10,
            'amount' => 100,
        ]);

        $this->po->items()->create([
            'sort_order' => 1,
            'product_id' => $this->formatLinkedProduct->id,
            'description' => 'Format-linked line',
            'unit' => 'pcs',
            'cost_price' => 10,
            'qty' => 10,
            'amount' => 100,
        ]);

        $this->po->items()->create([
            'sort_order' => 2,
            'product_id' => $this->excludedProduct->id,
            'description' => 'Excluded line',
            'unit' => 'pcs',
            'cost_price' => 10,
            'qty' => 10,
            'amount' => 100,
        ]);

        $this->po->items()->create([
            'sort_order' => 3,
            'product_id' => null,
            'design_no' => 'CUSTOM-1',
            'description' => 'Custom no-product line',
            'unit' => 'pcs',
            'cost_price' => 5,
            'qty' => 5,
            'amount' => 25,
        ]);
    }

    public function test_po_details_filters_items_by_oc_and_format_categories(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson(route('procurement.inward-entries.po-details', $this->po));

        $response->assertOk()
            ->assertJsonPath('excluded_count', 1)
            ->assertJsonPath('category_id', $this->ocCategory->id)
            ->assertJsonPath('document_format_id', $this->po->orderConfirmation->document_format_id)
            ->assertJsonPath('format_name', 'Filter Format');

        $names = collect($response->json('items'))->pluck('product_name')->all();

        $this->assertContains('Matching OC Product', $names);
        $this->assertContains('Format Category Product', $names);
        $this->assertContains('Custom Item', $names);
        $this->assertNotContains('Excluded Product', $names);

        $codes = collect($response->json('items'))->pluck('product_code')->all();
        $this->assertContains('PRD201', $codes);
        $this->assertContains('CUSTOM-1', $codes);
    }
}
