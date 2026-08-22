<?php

namespace Tests\Feature;

use App\Models\GarmentStyle;
use App\Models\ProductionOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManufacturingSizeBreakdownTest extends TestCase
{
    use RefreshDatabase;

    public function test_stage_size_grid_sums_into_cutting_qty(): void
    {
        $user = User::factory()->create();
        $style = GarmentStyle::create([
            'style_number' => 'ST-SIZE-1',
            'name'         => 'Size Grid Tee',
            'status'       => 'Active',
            'target_qty'   => 500,
        ]);

        $order = ProductionOrder::create([
            'order_number'     => 'PO-SIZE-1',
            'garment_style_id' => $style->id,
            'total_qty'        => 500,
            'target_date'      => now()->addDays(10),
            'current_stage'    => 'Cutting',
            'status'           => 'In Progress',
        ]);

        $this->actingAs($user)
            ->put(route('manufacturing.update', $order), [
                'order_number'     => 'PO-SIZE-1',
                'garment_style_id' => $style->id,
                'total_qty'        => 500,
                'target_date'      => now()->addDays(10)->format('Y-m-d'),
                'current_stage'    => 'Cutting',
                'status'           => 'In Progress',
                'job_work_type'    => 'printing',
                'sizes'            => [
                    'cutting' => [
                        'S' => 100, 'M' => 100, 'L' => 100, 'XL' => 100, 'XXL' => 100,
                        '3XL' => 0, '4XL' => 0, '5XL' => 0,
                    ],
                ],
            ])
            ->assertRedirect(route('manufacturing.index'));

        $order->refresh();
        $this->assertSame(500, $order->cutting_qty);
        $this->assertSame(100, $order->sizeQty('cutting', 'M'));
        $this->assertSame('printing', $order->job_work_type);
        $this->assertSame('cutting', $order->challanStageKey());
    }

    public function test_job_work_challan_pdf_downloads(): void
    {
        $user = User::factory()->create();
        $style = GarmentStyle::create([
            'style_number' => 'ST-SIZE-2',
            'name'         => 'Challan Tee',
            'status'       => 'Active',
        ]);
        $order = ProductionOrder::create([
            'order_number'     => 'PO-SIZE-2',
            'garment_style_id' => $style->id,
            'total_qty'        => 200,
            'target_date'      => now()->addDays(5),
            'current_stage'    => 'Cutting',
            'status'           => 'In Progress',
            'job_work_type'    => 'embroidery',
            'cutting_qty'      => 200,
            'size_breakdown'   => [
                'cutting' => ['S' => 40, 'M' => 40, 'L' => 40, 'XL' => 40, 'XXL' => 40],
            ],
        ]);

        $this->actingAs($user)
            ->get(route('manufacturing.job-work-challan', $order))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
