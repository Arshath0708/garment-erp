<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\GarmentStyle;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\User;
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
}
