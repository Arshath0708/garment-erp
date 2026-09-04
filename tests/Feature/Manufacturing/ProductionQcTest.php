<?php

namespace Tests\Feature\Manufacturing;

use App\Models\DefectCode;
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

    public function test_qc_fail_requires_defect_code_and_capa_plan(): void
    {
        $user = User::factory()->create();
        $style = GarmentStyle::create([
            'style_number' => 'ST-QC-0',
            'name'         => 'QC Tee',
            'status'       => 'Active',
            'target_qty'   => 100,
        ]);

        $order = ProductionOrder::create([
            'order_number'     => 'PO-QC-0',
            'garment_style_id' => $style->id,
            'total_qty'        => 100,
            'target_date'      => now()->addDays(10),
            'current_stage'    => 'Stitching',
            'status'           => 'In Progress',
        ]);

        $this->actingAs($user)
            ->from(route('manufacturing.edit', $order))
            ->post(route('manufacturing.qc-check', $order), [
                'stage'       => 'stitching',
                'checked_qty' => 10,
                'passed_qty'  => 7,
                'failed_qty'  => 3,
            ])
            ->assertRedirect(route('manufacturing.edit', $order))
            ->assertSessionHasErrors(['defect_code_id', 'capa_plan']);
    }

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

        $defect = DefectCode::query()->where('code', 'DF-ST-01')->firstOrFail();

        $this->actingAs($user)
            ->from(route('manufacturing.edit', $order))
            ->post(route('manufacturing.qc-check', $order), [
                'stage'           => 'stitching',
                'checked_qty'     => 10,
                'passed_qty'      => 7,
                'failed_qty'      => 3,
                'hold_work_order' => 1,
                'defect_code_id'  => $defect->id,
                'capa_plan'       => 'Retrain operator on lockstitch; recheck next bundle.',
                'capa_due_date'   => now()->addDays(2)->toDateString(),
                'notes'           => 'Broken stitch',
            ])
            ->assertRedirect(route('manufacturing.edit', $order))
            ->assertSessionHas('success');

        $check = ProductionQcCheck::query()->first();
        $this->assertNotNull($check);
        $this->assertSame('fail', $check->result);
        $this->assertTrue($check->held_work_order);
        $this->assertSame('open', $check->capa_status);
        $this->assertSame($defect->id, $check->defect_code_id);
        $this->assertSame('hold', $workOrder->fresh()->status);

        $this->actingAs($user)
            ->get(route('manufacturing.capa.index'))
            ->assertOk()
            ->assertSee('DF-ST-01')
            ->assertSee('PO-QC-1');

        $this->actingAs($user)
            ->post(route('manufacturing.capa.close', $check), [
                'close_note' => 'Operator trained; bundle ok.',
            ])
            ->assertRedirect();

        $this->assertSame('closed', $check->fresh()->capa_status);
        $this->assertNotNull($check->fresh()->capa_closed_at);
    }
}
