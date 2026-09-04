<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\GarmentStyle;
use App\Models\JobWorkVoucher;
use App\Models\Product;
use App\Models\ProductionLine;
use App\Models\ProductionOrder;
use App\Models\StyleCostingLine;
use App\Models\User;
use App\Support\FinancialYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FactoryOpsTest extends TestCase
{
    use RefreshDatabase;

    public function test_approving_bom_stamps_version_and_material_edit_clears_it(): void
    {
        $user = User::factory()->create();
        [$style, $fabric] = $this->styleWithMaterial();

        $this->actingAs($user)
            ->post(route('masters.styles.approve-bom', $style))
            ->assertRedirect(route('masters.styles.show', $style));

        $style->refresh();
        $this->assertTrue($style->isBomApproved());
        $this->assertSame(1, (int) $style->bom_version);
        $this->assertDatabaseHas('garment_style_bom_snapshots', [
            'garment_style_id' => $style->id,
            'version'          => 1,
        ]);

        $this->actingAs($user)
            ->put(route('masters.styles.update', $style), [
                'style_number' => $style->style_number,
                'name'         => $style->name,
                'target_qty'   => $style->target_qty,
                'status'       => $style->status,
                'materials'    => [[
                    'product_id' => $fabric->id,
                    'qty_per_pc' => 2,
                    'unit'       => 'kg',
                ]],
            ])
            ->assertRedirect();

        $style->refresh();
        $this->assertFalse($style->isBomApproved());
        $this->assertSame(2, (int) $style->bom_version);
        $this->assertNull($style->bom_approved_at);
    }

    public function test_line_output_shows_efficiency_against_daily_target(): void
    {
        foreach (['work-order.view', 'work-order.edit'] as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }
        $user = User::factory()->create();
        $user->givePermissionTo(['work-order.view', 'work-order.edit']);

        $line = ProductionLine::query()->where('name', 'Line 1')->first();
        $this->assertNotNull($line);
        $this->assertSame(500, (int) $line->target_pcs_per_day);

        $this->actingAs($user)
            ->post(route('production-lines.outputs.store'), [
                'production_line_id' => $line->id,
                'output_date'        => now()->toDateString(),
                'pcs'                => 250,
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->get(route('production-lines.index'))
            ->assertOk()
            ->assertSee('50.0%', false)
            ->assertSee('Line 1', false);
    }

    public function test_order_profit_uses_costing_stock_and_job_work_charges(): void
    {
        Permission::firstOrCreate(['name' => 'report.view']);
        $user = User::factory()->create();
        $user->givePermissionTo('report.view');

        [$style, $fabric] = $this->styleWithMaterial();
        $costing = $this->approveStyleCosting($style, 5);
        $costing->update([
            'material_cost'     => 8,
            'total_cost_per_pc' => 13,
        ]);
        StyleCostingLine::create([
            'style_costing_id' => $costing->id,
            'product_id'       => $fabric->id,
            'description'      => $fabric->name,
            'item_kind'        => 'fabric',
            'qty_per_pc'       => 1,
            'unit'             => 'kg',
            'rate'             => 8,
            'amount'           => 8,
            'sort_order'       => 0,
        ]);

        $order = ProductionOrder::create([
            'order_number'     => 'PO-PROFIT-1',
            'garment_style_id' => $style->id,
            'total_qty'        => 10,
            'cutting_qty'      => 10,
            'target_date'      => now()->addDays(10),
            'current_stage'    => 'Cutting',
            'status'           => 'In Progress',
        ]);
        $order->materials()->create([
            'product_id'    => $fabric->id,
            'required_qty'  => 10,
            'use_stock_qty' => 4,
            'buy_qty'       => 6,
        ]);

        $jobber = \App\Models\Supplier::create([
            'display_code' => 'JBW-PF',
            'party_type'   => 'jobber',
            'company_name' => 'Profit Jobber',
            'status'       => 'active',
        ]);

        $voucher = new JobWorkVoucher([
            'voucher_date'        => now()->toDateString(),
            'type'                => 'receive',
            'jobber_id'           => $jobber->id,
            'production_order_id' => $order->id,
            'garment_style_id'    => $style->id,
            'total_qty'           => 10,
            'damaged_qty'         => 0,
            'rate_per_pc'         => 4,
            'charge_amount'       => 40,
        ]);
        $voucher->financial_year = FinancialYear::current();
        $voucher->voucher_num = 'JW/T/001';
        $voucher->save();

        $this->actingAs($user)
            ->get(route('reports.order-profit'))
            ->assertOk()
            ->assertSee('PO-PROFIT-1', false)
            ->assertSee('130.00', false)
            ->assertSee('122.00', false);
    }

    public function test_inventory_lists_items_below_reorder_level_with_raise_po(): void
    {
        Permission::firstOrCreate(['name' => 'purchase-order.create']);
        $user = User::factory()->create();
        $user->givePermissionTo('purchase-order.create');
        $category = new Category(['name' => 'Trims', 'status' => 'active']);
        $category->code = 'TRM-RO';
        $category->save();

        $product = Product::create([
            'category_id'     => $category->id,
            'item_group_code' => 'RO'.substr(uniqid(), -6),
            'name'            => 'Low Zipper',
            'status'          => 'active',
            'item_kind'       => 'accessory',
            'qty_on_hand'     => 5,
            'reorder_level'   => 10,
            'unit_po'         => 'pcs',
        ]);

        $this->actingAs($user)
            ->get(route('inventory.index'))
            ->assertOk()
            ->assertSee('Low Zipper', false)
            ->assertSee('Low stock', false)
            ->assertSee('Raise PO', false)
            ->assertSee('product_id='.$product->id, false);

        $this->actingAs($user)
            ->get(route('procurement.purchase-orders.create', ['product_id' => $product->id]))
            ->assertOk()
            ->assertSee('Low Zipper', false);
    }

    /**
     * @return array{0: GarmentStyle, 1: Product}
     */
    private function styleWithMaterial(): array
    {
        $category = new Category(['name' => 'Ops fabric', 'status' => 'active']);
        $category->code = 'FAB-OPS-'.substr(uniqid(), -4);
        $category->save();

        $fabric = Product::create([
            'category_id'     => $category->id,
            'item_group_code' => 'OPS'.substr(uniqid(), -6),
            'name'            => 'Ops Cotton',
            'status'          => 'active',
            'item_kind'       => 'fabric',
            'qty_on_hand'     => 100,
            'unit_po'         => 'kg',
        ]);

        $style = GarmentStyle::create([
            'style_number' => 'ST-OPS-'.uniqid(),
            'name'         => 'Ops Tee',
            'status'       => 'Active',
            'target_qty'   => 100,
        ]);
        $style->materials()->create([
            'product_id' => $fabric->id,
            'qty_per_pc' => 1,
            'unit'       => 'kg',
            'sort_order' => 0,
        ]);

        return [$style, $fabric];
    }
}
