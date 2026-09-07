<?php

namespace Tests\Feature;

use App\Models\Buyer;
use App\Models\Category;
use App\Models\Currency;
use App\Models\DocumentFormat;
use App\Models\GarmentStyle;
use App\Models\Inquiry;
use App\Models\ProductionOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AssignedFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'inquiry.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'inquiry.edit', 'guard_name' => 'web']);

        Role::firstOrCreate(['name' => 'Super Admin']);
        $merchRole = Role::firstOrCreate(['name' => 'Merchandising & Manufacturing']);
        $merchRole->givePermissionTo(['inquiry.view', 'inquiry.edit']);
    }

    public function test_feature_1_dual_style_numbers(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $response = $this->actingAs($admin)->post(route('masters.styles.store'), [
            'style_number' => 'ST10002',
            'buyer_style_no' => 'BST-9942',
            'factory_style_no' => 'FST-3301',
            'name' => 'Men Polo Shirt',
            'target_qty' => 500,
            'status' => 'Active',
        ]);

        $response->assertRedirect(route('masters.styles.index'));
        $this->assertDatabaseHas('garment_styles', [
            'style_number' => 'ST10002',
            'buyer_style_no' => 'BST-9942',
            'factory_style_no' => 'FST-3301',
        ]);

        $style = GarmentStyle::where('style_number', 'ST10002')->first();
        $showResponse = $this->actingAs($admin)->get(route('masters.styles.show', $style));
        $showResponse->assertSee('BST-9942');
        $showResponse->assertSee('FST-3301');
    }

    public function test_feature_2_style_tech_pack_comments_and_production_display(): void
    {
        $admin = User::factory()->create(['name' => 'John Merchandiser']);
        $admin->assignRole('Super Admin');

        $style = GarmentStyle::create([
            'style_number' => 'ST10003',
            'buyer_style_no' => 'BST-9943',
            'factory_style_no' => 'FST-3302',
            'name' => 'Women Blouse',
            'target_qty' => 300,
            'status' => 'Active',
        ]);

        $commentResponse = $this->actingAs($admin)->post(route('masters.styles.comments.store', $style), [
            'comment' => 'Use double-stitched collar seams per buyer approval.',
        ]);

        $commentResponse->assertRedirect(route('masters.styles.show', $style->id));
        $this->assertDatabaseHas('garment_style_comments', [
            'garment_style_id' => $style->id,
            'user_name' => 'John Merchandiser',
            'comment' => 'Use double-stitched collar seams per buyer approval.',
        ]);

        $prodOrder = ProductionOrder::create([
            'order_number' => 'PO-2026-001',
            'garment_style_id' => $style->id,
            'total_qty' => 300,
            'current_stage' => 'Cutting',
            'status' => 'In Progress',
        ]);

        $prodResponse = $this->actingAs($admin)->get(route('manufacturing.index'));
        $prodResponse->assertSee('FST-3302');
        $prodResponse->assertSee('Use double-stitched collar seams per buyer approval.');
    }

    public function test_feature_3_hide_cost_price_from_unauthorized_users(): void
    {
        Permission::firstOrCreate(['name' => 'cost-price.view', 'guard_name' => 'web']);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');

        $regularUser = User::factory()->create();
        $regularUser->assignRole('Merchandising & Manufacturing');

        $accounts = User::factory()->create();
        $accountsRole = Role::firstOrCreate(['name' => 'Accounts']);
        $accountsRole->givePermissionTo(['inquiry.view', 'cost-price.view']);
        $accounts->assignRole('Accounts');

        $style = GarmentStyle::create([
            'style_number' => 'ST10004',
            'name' => 'Casual Jacket',
            'target_qty' => 100,
            'status' => 'Active',
        ]);

        $buyer = Buyer::forceCreate(['display_code' => 'BUY01', 'company_name' => 'ABC Fashion Ltd']);
        $cat = Category::forceCreate(['code' => 'WVN', 'name' => 'Woven', 'status' => 'active']);
        $df = DocumentFormat::forceCreate(['name' => 'Standard']);
        $cur = Currency::forceCreate(['name' => 'INR', 'iso_code' => 'INR', 'symbol' => '₹']);

        $inquiry = Inquiry::forceCreate([
            'inquiry_no' => 'INQ-2026-001',
            'financial_year' => '2026',
            'inquiry_date' => now()->toDateString(),
            'buyer_id' => $buyer->id,
            'category_id' => $cat->id,
            'document_format_id' => $df->id,
            'currency_id' => $cur->id,
        ]);

        $item = $inquiry->items()->create([
            'design_no' => 'ST10004',
            'price' => 120.00,
            'cost_price' => 75.00,
            'qty' => 100,
            'amount' => 12000.00,
        ]);

        $adminView = $this->actingAs($superAdmin)->get(route('sales.inquiries.show', $inquiry));
        $adminView->assertSee('Cost Price');
        $adminView->assertSee('75.00');

        $this->actingAs($accounts)
            ->get(route('sales.inquiries.show', $inquiry))
            ->assertSee('Cost Price')
            ->assertSee('75.00');

        $regularView = $this->actingAs($regularUser)->get(route('sales.inquiries.show', $inquiry));
        $regularView->assertDontSee('Cost Price');
        $regularView->assertSee('120.00');

        // Crafted cost_price in the POST must be ignored — existing value stays.
        $this->actingAs($regularUser)->put(route('sales.inquiries.update', $inquiry), [
            'mode' => 'draft',
            'status' => 'draft',
            'inquiry_date' => now()->toDateString(),
            'buyer_id' => $buyer->id,
            'category_id' => $cat->id,
            'document_format_id' => $df->id,
            'currency_id' => $cur->id,
            'delivery_details' => 'Factory',
            'packing_details' => 'Cartons',
            'items' => [
                [
                    'design_no' => 'ST10004',
                    'price' => 125.00,
                    'cost_price' => 1.00,
                    'colours' => [
                        ['colour' => 'Navy', 'sizes' => [['size' => 'M', 'qty' => 100]]],
                    ],
                ],
            ],
        ])->assertRedirect();

        $saved = $inquiry->fresh('items')->items->first();
        $this->assertNotNull($saved);
        $this->assertEquals(75.00, (float) $saved->cost_price);
        $this->assertEquals(125.00, (float) $saved->price);
    }
}
