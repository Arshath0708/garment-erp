<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\GarmentStyle;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\User;
use App\Services\Manufacturing\WorkOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_using_stock_on_a_repeat_order_reduces_qty_on_hand(): void
    {
        $user = User::factory()->create();
        $category = Category::forceCreate([
            'code' => 'FAB01',
            'name' => 'Fabric items',
            'status' => 'active',
        ]);
        $fabric = Product::create([
            'category_id'     => $category->id,
            'item_group_code' => 'CTN01',
            'name'            => 'Cotton Twill 180GSM',
            'status'          => 'active',
            'item_kind'       => 'fabric',
            'qty_on_hand'     => 8000,
            'unit_po'         => 'kg',
        ]);
        $style = GarmentStyle::create([
            'style_number' => 'ST-1001',
            'name'         => "Men's Basic T-Shirt",
            'status'       => 'Active',
            'target_qty'   => 10000,
        ]);
        $style->materials()->create([
            'product_id' => $fabric->id,
            'qty_per_pc' => 1,
            'unit'       => 'kg',
            'sort_order' => 0,
        ]);

        $this->actingAs($user)
            ->post(route('manufacturing.store'), [
                'order_number'     => 'PO-INV-1',
                'work_order_id'    => $this->releasedWorkOrder($style, 10000)->id,
                'garment_style_id' => $style->id,
                'total_qty'        => 10000,
                'target_date'      => now()->addDays(10)->format('Y-m-d'),
                'job_work_type'    => 'in_house',
                'materials'        => [
                    ['product_id' => $fabric->id, 'use_stock_qty' => 8000],
                ],
            ])
            ->assertRedirect(route('manufacturing.index'));

        $this->assertEquals('0.000', (string) Product::find($fabric->id)->qty_on_hand);
        $order = ProductionOrder::where('order_number', 'PO-INV-1')->first();
        $this->assertNotNull($order);
        $line = $order->materials()->first();
        $this->assertEquals('8000.000', (string) $line->use_stock_qty);
        $this->assertEquals('2000.000', (string) $line->buy_qty);
    }

    public function test_cannot_use_more_stock_than_on_hand(): void
    {
        $user = User::factory()->create();
        $category = Category::forceCreate([
            'code' => 'FAB02',
            'name' => 'Trim items',
            'status' => 'active',
        ]);
        $button = Product::create([
            'category_id'     => $category->id,
            'item_group_code' => 'BTN01',
            'name'            => 'Resin Buttons',
            'status'          => 'active',
            'item_kind'       => 'accessory',
            'qty_on_hand'     => 100,
            'unit_po'         => 'pcs',
        ]);
        $style = GarmentStyle::create([
            'style_number' => 'ST-1002',
            'name'         => 'Button Tee',
            'status'       => 'Active',
            'target_qty'   => 50,
        ]);
        $style->materials()->create([
            'product_id' => $button->id,
            'qty_per_pc' => 3,
            'unit'       => 'pcs',
        ]);

        $this->actingAs($user)
            ->from(route('manufacturing.create'))
            ->post(route('manufacturing.store'), [
                'order_number'     => 'PO-INV-2',
                'work_order_id'    => $this->releasedWorkOrder($style, 50)->id,
                'garment_style_id' => $style->id,
                'total_qty'        => 50,
                'target_date'      => now()->addDays(5)->format('Y-m-d'),
                'job_work_type'    => 'in_house',
                'materials'        => [
                    ['product_id' => $button->id, 'use_stock_qty' => 500],
                ],
            ])
            ->assertRedirect(route('manufacturing.create'))
            ->assertSessionHasErrors();

        $this->assertEquals('100.000', (string) Product::find($button->id)->qty_on_hand);
    }

    public function test_zipper_size_range_uses_cutting_qty_in_that_range(): void
    {
        $category = Category::forceCreate([
            'code' => 'TRM01',
            'name' => 'Trim items',
            'status' => 'active',
        ]);
        $shortZip = Product::create([
            'category_id'     => $category->id,
            'item_group_code' => 'ZIP55',
            'name'            => 'Zipper 5.5 inch',
            'status'          => 'active',
            'item_kind'       => 'accessory',
            'qty_on_hand'     => 0,
            'unit_po'         => 'pcs',
        ]);
        $longZip = Product::create([
            'category_id'     => $category->id,
            'item_group_code' => 'ZIP60',
            'name'            => 'Zipper 6 inch',
            'status'          => 'active',
            'item_kind'       => 'accessory',
            'qty_on_hand'     => 0,
            'unit_po'         => 'pcs',
        ]);
        $style = GarmentStyle::create([
            'style_number' => 'ST-ZIP-1',
            'name'         => 'Zip Hoodie',
            'status'       => 'Active',
            'target_qty'   => 100,
        ]);
        $style->materials()->create([
            'product_id' => $shortZip->id,
            'qty_per_pc' => 1,
            'unit'       => 'pcs',
            'size_from'  => 'S',
            'size_to'    => 'M',
            'sort_order' => 0,
        ]);
        $style->materials()->create([
            'product_id' => $longZip->id,
            'qty_per_pc' => 1,
            'unit'       => 'pcs',
            'size_from'  => 'L',
            'size_to'    => 'XL',
            'sort_order' => 1,
        ]);

        $order = ProductionOrder::create([
            'order_number'     => 'PO-ZIP-1',
            'work_order_id'    => $this->releasedWorkOrder($style, 100)->id,
            'garment_style_id' => $style->id,
            'total_qty'        => 100,
            'target_date'      => now()->addDays(10),
            'current_stage'    => 'Cutting',
            'status'           => 'In Progress',
            'size_breakdown'   => [
                'cutting' => ['S' => 50, 'M' => 30, 'L' => 20, 'XL' => 0],
            ],
        ]);

        $rows = app(\App\Services\Inventory\MaterialPlanService::class)->preview($style, 100, $order);
        $byProduct = collect($rows)->keyBy('product_id');

        $this->assertSame(80.0, $byProduct[$shortZip->id]['required_qty']);
        $this->assertSame(20.0, $byProduct[$longZip->id]['required_qty']);
        $this->assertSame('S–M', $byProduct[$shortZip->id]['size_range']);
        $this->assertSame('L–XL', $byProduct[$longZip->id]['size_range']);
    }

    public function test_same_trim_can_appear_twice_for_different_size_ranges(): void
    {
        $category = Category::forceCreate([
            'code' => 'TRM02',
            'name' => 'Buttons',
            'status' => 'active',
        ]);
        $button = Product::create([
            'category_id'     => $category->id,
            'item_group_code' => 'BTN02',
            'name'            => 'Horn Button',
            'status'          => 'active',
            'item_kind'       => 'accessory',
            'qty_on_hand'     => 500,
            'unit_po'         => 'pcs',
        ]);
        $style = GarmentStyle::create([
            'style_number' => 'ST-BTN-1',
            'name'         => 'Button Shirt',
            'status'       => 'Active',
            'target_qty'   => 100,
        ]);
        $style->materials()->create([
            'product_id' => $button->id,
            'qty_per_pc' => 6,
            'unit'       => 'pcs',
            'size_from'  => 'S',
            'size_to'    => 'M',
            'sort_order' => 0,
        ]);
        $style->materials()->create([
            'product_id' => $button->id,
            'qty_per_pc' => 8,
            'unit'       => 'pcs',
            'size_from'  => 'L',
            'size_to'    => 'XL',
            'sort_order' => 1,
        ]);

        $order = ProductionOrder::create([
            'order_number'     => 'PO-BTN-1',
            'work_order_id'    => $this->releasedWorkOrder($style, 100)->id,
            'garment_style_id' => $style->id,
            'total_qty'        => 100,
            'target_date'      => now()->addDays(10),
            'size_breakdown'   => [
                'cutting' => ['S' => 40, 'M' => 40, 'L' => 20, 'XL' => 0],
            ],
        ]);

        app(\App\Services\Inventory\MaterialPlanService::class)->apply($order);
        $line = $order->fresh('materials')->materials->first();

        // 6×80 pcs (S–M) + 8×20 pcs (L–XL) = 640
        $this->assertSame(2, $style->materials()->count());
        $this->assertEquals('640.000', (string) $line->required_qty);
    }

    private function releasedWorkOrder(GarmentStyle $style, int $qty): \App\Models\WorkOrder
    {
        $service = app(WorkOrderService::class);
        $workOrder = $service->create([
            'wo_date'          => now()->toDateString(),
            'garment_style_id' => $style->id,
            'total_qty'        => $qty,
            'target_date'      => now()->addDays(30)->toDateString(),
        ]);

        $this->approveStyleCosting($style);

        return $service->release($workOrder);
    }
}
