<?php

namespace Tests\Feature\Sales;

use App\Models\Buyer;
use App\Models\Category;
use App\Models\Currency;
use App\Models\DocumentFormat;
use App\Models\Inquiry;
use App\Models\InquiryItemBomLine;
use App\Models\Product;
use App\Models\ProductBomItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InquiryBomAndUnitsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Category $category;
    private DocumentFormat $format;
    private Buyer $buyer;
    private Currency $currency;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->admin = User::factory()->create(['status' => true]);
        $this->admin->assignRole('Super Admin');

        $this->category = Category::forceCreate([
            'code' => 'CAT101',
            'name' => 'Inquiry Test Category',
            'status' => 'active',
        ]);

        $this->format = DocumentFormat::create([
            'name' => 'Inquiry Test Format',
            'status' => 'active',
        ]);
        $this->format->units()->create(['name' => 'PCS', 'sort_order' => 0]);
        $this->format->units()->create(['name' => 'SET', 'sort_order' => 1]);
        $this->format->categories()->attach($this->category->id);

        $this->buyer = Buyer::forceCreate([
            'display_code' => 'BUY101',
            'company_name' => 'Inquiry Test Buyer',
            'status' => 'active',
        ]);

        $this->currency = Currency::query()->where('iso_code', 'USD')->first()
            ?? Currency::create([
                'iso_code' => 'USD',
                'name' => 'US Dollar',
                'symbol' => '$',
                'status' => 'active',
            ]);

        $this->product = Product::create([
            'category_id' => $this->category->id,
            'item_group_code' => 'PRD101',
            'name' => 'Inquiry BOM Shirt',
            'unit_po' => 'DOZ',
            'unit_export' => 'KGS',
            'status' => 'active',
        ]);

        ProductBomItem::create([
            'product_id' => $this->product->id,
            'sort_order' => 0,
            'component_name' => 'Main Fabric',
            'qty' => 1.5,
            'unit' => 'MTR',
            'is_custom' => false,
            'remarks' => 'From product master',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function inquiryPayload(array $overrides = []): array
    {
        return array_merge([
            'mode' => 'draft',
            'inquiry_date' => now()->toDateString(),
            'source' => 'direct',
            'buyer_id' => $this->buyer->id,
            'category_id' => $this->category->id,
            'document_format_id' => $this->format->id,
            'currency_id' => $this->currency->id,
            'delivery_details' => 'FOB',
            'packing_details' => 'Carton',
            'status' => 'draft',
            'items' => [
                [
                    'design_no' => 'D-1',
                    'description' => 'Test line',
                    'product_id' => $this->product->id,
                    'unit' => 'PCS',
                    'price' => 10,
                    'status' => 'draft',
                    'colours' => [
                        [
                            'colour' => 'Blue',
                            'sizes' => [
                                ['size' => 'M', 'qty' => 5],
                            ],
                        ],
                    ],
                    'bom' => [
                        [
                            'component_name' => 'Main Fabric',
                            'qty' => 1.5,
                            'unit' => 'MTR',
                            'is_custom' => 0,
                            'remarks' => 'Snapshotted',
                        ],
                        [
                            'component_name' => 'Custom Thread',
                            'qty' => 2,
                            'unit' => 'PCS',
                            'is_custom' => 1,
                            'remarks' => 'Added on inquiry',
                        ],
                    ],
                ],
            ],
        ], $overrides);
    }

    public function test_products_endpoint_returns_units_and_bom(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson(route('sales.inquiries.products', ['category_id' => $this->category->id]));

        $response->assertOk();

        $row = collect($response->json())->firstWhere('id', $this->product->id);

        $this->assertNotNull($row);
        $this->assertSame('DOZ', $row['unit_po']);
        $this->assertSame('KGS', $row['unit_export']);
        $this->assertCount(1, $row['bom']);
        $this->assertSame('Main Fabric', $row['bom'][0]['component_name']);
        $this->assertSame(1.5, (float) $row['bom'][0]['qty']);
    }

    public function test_inquiry_draft_persists_item_bom_snapshot(): void
    {
        $this->actingAs($this->admin)
            ->post(route('sales.inquiries.store'), $this->inquiryPayload())
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $inquiry = Inquiry::with('items.bomLines')->first();
        $this->assertNotNull($inquiry);
        $this->assertCount(1, $inquiry->items);

        $bom = $inquiry->items->first()->bomLines;
        $this->assertCount(2, $bom);
        $this->assertSame('Main Fabric', $bom[0]->component_name);
        $this->assertFalse((bool) $bom[0]->is_custom);
        $this->assertSame('Custom Thread', $bom[1]->component_name);
        $this->assertTrue((bool) $bom[1]->is_custom);
        $this->assertSame(2, InquiryItemBomLine::count());
    }

    public function test_unit_from_order_format_is_accepted(): void
    {
        $this->actingAs($this->admin)
            ->post(route('sales.inquiries.store'), $this->inquiryPayload([
                'items' => [[
                    'product_id' => $this->product->id,
                    'unit' => 'SET',
                    'status' => 'draft',
                    'colours' => [['colour' => null, 'sizes' => [['size' => 'M', 'qty' => 1]]]],
                ]],
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    public function test_product_unit_export_is_accepted_even_if_missing_from_format(): void
    {
        $this->actingAs($this->admin)
            ->post(route('sales.inquiries.store'), $this->inquiryPayload([
                'items' => [[
                    'product_id' => $this->product->id,
                    'unit' => 'KGS',
                    'status' => 'draft',
                    'colours' => [['colour' => null, 'sizes' => [['size' => 'M', 'qty' => 1]]]],
                ]],
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    public function test_product_unit_po_is_accepted_even_if_missing_from_format(): void
    {
        $this->actingAs($this->admin)
            ->post(route('sales.inquiries.store'), $this->inquiryPayload([
                'items' => [[
                    'product_id' => $this->product->id,
                    'unit' => 'DOZ',
                    'status' => 'draft',
                    'colours' => [['colour' => null, 'sizes' => [['size' => 'M', 'qty' => 1]]]],
                ]],
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    public function test_unknown_unit_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post(route('sales.inquiries.store'), $this->inquiryPayload([
                'items' => [[
                    'product_id' => $this->product->id,
                    'unit' => 'BAG',
                    'status' => 'draft',
                    'colours' => [['colour' => null, 'sizes' => [['size' => 'M', 'qty' => 1]]]],
                ]],
            ]))
            ->assertSessionHasErrors('items.0.unit');
    }

    public function test_legacy_saved_unit_is_accepted_on_resave(): void
    {
        // UI keeps a previously saved unit via ensureUnitOption when it is no
        // longer on the format; update validation must allow that legacy value.
        $this->actingAs($this->admin)
            ->post(route('sales.inquiries.store'), $this->inquiryPayload([
                'items' => [[
                    'product_id' => $this->product->id,
                    'unit' => 'PCS',
                    'status' => 'draft',
                    'colours' => [['colour' => null, 'sizes' => [['size' => 'M', 'qty' => 1]]]],
                ]],
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $inquiry = Inquiry::first();
        $inquiry->items()->first()->update(['unit' => 'YDS']);

        $this->actingAs($this->admin)
            ->put(route('sales.inquiries.update', $inquiry), $this->inquiryPayload([
                'items' => [[
                    'product_id' => $this->product->id,
                    'unit' => 'YDS',
                    'status' => 'draft',
                    'colours' => [['colour' => null, 'sizes' => [['size' => 'M', 'qty' => 1]]]],
                ]],
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('YDS', $inquiry->fresh()->items()->first()->unit);
    }

    public function test_new_unknown_unit_is_still_rejected_on_update(): void
    {
        $this->actingAs($this->admin)
            ->post(route('sales.inquiries.store'), $this->inquiryPayload([
                'items' => [[
                    'product_id' => $this->product->id,
                    'unit' => 'PCS',
                    'status' => 'draft',
                    'colours' => [['colour' => null, 'sizes' => [['size' => 'M', 'qty' => 1]]]],
                ]],
            ]))
            ->assertRedirect();

        $inquiry = Inquiry::first();

        $this->actingAs($this->admin)
            ->put(route('sales.inquiries.update', $inquiry), $this->inquiryPayload([
                'items' => [[
                    'product_id' => $this->product->id,
                    'unit' => 'BAG',
                    'status' => 'draft',
                    'colours' => [['colour' => null, 'sizes' => [['size' => 'M', 'qty' => 1]]]],
                ]],
            ]))
            ->assertSessionHasErrors('items.0.unit');
    }

    public function test_inquiry_bom_is_custom_flag_is_preserved_on_update(): void
    {
        $this->actingAs($this->admin)
            ->post(route('sales.inquiries.store'), $this->inquiryPayload())
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $inquiry = Inquiry::with('items.bomLines')->first();
        $this->assertFalse((bool) $inquiry->items->first()->bomLines[0]->is_custom);
        $this->assertTrue((bool) $inquiry->items->first()->bomLines[1]->is_custom);

        $this->actingAs($this->admin)
            ->put(route('sales.inquiries.update', $inquiry), $this->inquiryPayload([
                'items' => [[
                    'product_id' => $this->product->id,
                    'unit' => 'PCS',
                    'status' => 'draft',
                    'colours' => [['colour' => null, 'sizes' => [['size' => 'M', 'qty' => 1]]]],
                    'bom' => [
                        [
                            'component_name' => 'Main Fabric',
                            'qty' => 1.5,
                            'unit' => 'MTR',
                            'is_custom' => 0,
                        ],
                        [
                            'component_name' => 'Custom Thread',
                            'qty' => 2,
                            'unit' => 'PCS',
                            'is_custom' => 1,
                        ],
                    ],
                ]],
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $bom = $inquiry->fresh()->items()->first()->bomLines;
        $this->assertFalse((bool) $bom[0]->is_custom);
        $this->assertTrue((bool) $bom[1]->is_custom);
    }
}
