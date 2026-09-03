<?php

namespace Tests\Feature\Manufacturing;

use App\Models\GarmentStyle;
use App\Models\ProductionOrder;
use App\Models\ProductionQcCheck;
use App\Models\User;
use App\Services\Manufacturing\WorkOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionQcTest extends TestCase
{
    use RefreshDatabase;

    public function test_qc_fail_with_hold_puts_released_work_order_on_hold(): void
    {
        $user = User::factory()->create();
        $style = GarmentStyle::create([
            'style_number' => 'ST-QC-1',
            'name'         => 'QC Tee',
            'status'       => 'Active',
            'target_qty'   => 100,
        ]);

        $workOrders = app(WorkOrderService::class);
        $created = $workOrders->create([
            'wo_date'          => now()->toDateString(),
            'garment_style_id' => $style->id,
            'total_qty'        => 100,
            'target_date'      => now()->addDays(30)->toDateString(),
        ]);
        $this->approveStyleCosting($style);
        $workOrder = $workOrders->release($created);

        $order = ProductionOrder::create([
            'order_number'     => 'PO-QC-1',
            'work_order_id'    => $workOrder->id,
            'garment_style_id' => $style->id,
            'total_qty'        => 100,
            'target_date'      => now()->addDays(10),
            'current_stage'    => 'Stitching',
            'status'           => 'In Progress',
        ]);

        $this->actingAs($user)
            ->from(route('manufacturing.edit', $order))
            ->post(route('manufacturing.qc-check', $order), [
                'stage'           => 'stitching',
                'checked_qty'     => 10,
                'passed_qty'      => 7,
                'failed_qty'      => 3,
                'hold_work_order' => 1,
                'notes'           => 'Broken stitch',
            ])
            ->assertRedirect(route('manufacturing.edit', $order))
            ->assertSessionHas('success');

        $check = ProductionQcCheck::query()->first();
        $this->assertNotNull($check);
        $this->assertSame('fail', $check->result);
        $this->assertTrue($check->held_work_order);
        $this->assertSame('hold', $workOrder->fresh()->status);
    }
}
