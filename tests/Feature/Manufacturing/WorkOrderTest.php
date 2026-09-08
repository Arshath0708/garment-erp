<?php

namespace Tests\Feature\Manufacturing;

use App\Models\GarmentStyle;
use App\Models\ProductionOrder;
use App\Models\TimeAndActionStep;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\Manufacturing\WorkOrderService;
use App\Support\FinancialYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class WorkOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private WorkOrderService $workOrders;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'work-order.view',
            'work-order.create',
            'work-order.edit',
            'work-order.delete',
            'work-order.approve',
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $this->user = User::factory()->create();
        $this->user->givePermissionTo([
            'work-order.view',
            'work-order.create',
            'work-order.edit',
            'work-order.delete',
            'work-order.approve',
        ]);

        $this->workOrders = app(WorkOrderService::class);
    }

    public function test_creating_a_work_order_seeds_tna_dates_from_target(): void
    {
        $style = $this->style();
        $target = '2026-10-31';

        $this->actingAs($this->user)
            ->post(route('work-orders.store'), [
                'wo_date'          => '2026-09-01',
                'garment_style_id' => $style->id,
                'total_qty'        => 500,
                'target_date'      => $target,
            ])
            ->assertRedirect();

        $workOrder = WorkOrder::query()->first();
        $this->assertNotNull($workOrder);
        $this->assertSame('draft', $workOrder->status);
        $this->assertSame('WO/'.FinancialYear::current().'/001', $workOrder->wo_num);
        $this->assertCount(8, $workOrder->steps);

        $dispatch = $workOrder->step('dispatch');
        $this->assertNotNull($dispatch);
        $this->assertSame($target, $dispatch->planned_date->toDateString());
        $this->assertSame('2026-10-06', $workOrder->step('fabric_inward')?->planned_date->toDateString());
        $this->assertSame('2026-10-13', $workOrder->step('cutting')?->planned_date->toDateString());
    }

    public function test_cannot_launch_production_without_a_released_work_order(): void
    {
        $style = $this->style();

        $this->actingAs($this->user)
            ->from(route('manufacturing.create'))
            ->post(route('manufacturing.store'), $this->productionPayload($style))
            ->assertRedirect(route('manufacturing.create'))
            ->assertSessionHasErrors('work_order_id');

        $this->assertDatabaseCount('production_orders', 0);
    }

    public function test_draft_and_hold_work_orders_block_production_launch(): void
    {
        $style = $this->style();
        $draft = $this->makeWorkOrder($style);

        $this->actingAs($this->user)
            ->from(route('manufacturing.create'))
            ->post(route('manufacturing.store'), $this->productionPayload($style, $draft))
            ->assertRedirect(route('manufacturing.create'))
            ->assertSessionHasErrors('work_order_id');

        $released = $this->workOrders->release($draft);
        $this->workOrders->hold($released);

        $this->actingAs($this->user)
            ->from(route('manufacturing.create'))
            ->post(route('manufacturing.store'), $this->productionPayload($style, $released->fresh()))
            ->assertRedirect(route('manufacturing.create'))
            ->assertSessionHasErrors('work_order_id');

        $this->assertDatabaseCount('production_orders', 0);
    }

    public function test_released_work_order_allows_production_launch(): void
    {
        $style = $this->style();
        $workOrder = $this->workOrders->release($this->makeWorkOrder($style));

        $this->actingAs($this->user)
            ->post(route('manufacturing.store'), $this->productionPayload($style, $workOrder, [
                'order_number' => 'PO-WO-OK',
            ]))
            ->assertRedirect(route('manufacturing.index'));

        $order = ProductionOrder::query()->where('order_number', 'PO-WO-OK')->first();
        $this->assertNotNull($order);
        $this->assertSame($workOrder->id, $order->work_order_id);
    }

    public function test_late_tna_steps_show_on_the_late_filter(): void
    {
        $style = $this->style();
        $workOrder = $this->workOrders->release($this->makeWorkOrder($style, [
            'target_date' => now()->subDays(5)->toDateString(),
        ]));

        $this->assertTrue($workOrder->fresh('steps')->step('dispatch')?->isLate());

        $this->actingAs($this->user)
            ->get(route('time-and-action.index', ['late' => 1]))
            ->assertOk()
            ->assertSee($workOrder->wo_num, false)
            ->assertSee('Late', false)
            ->assertSee('table-danger', false);
    }

    public function test_cutting_qty_sets_tna_actual_date(): void
    {
        $style = $this->style();
        $workOrder = $this->workOrders->release($this->makeWorkOrder($style, ['total_qty' => 100]));

        $this->actingAs($this->user)
            ->post(route('manufacturing.store'), $this->productionPayload($style, $workOrder, [
                'order_number' => 'PO-CUT-1',
                'total_qty'    => 100,
                'sizes'        => [
                    'cutting' => ['S' => 40, 'M' => 60],
                ],
            ]))
            ->assertRedirect(route('manufacturing.index'));

        $cutting = TimeAndActionStep::query()
            ->where('work_order_id', $workOrder->id)
            ->where('step_key', 'cutting')
            ->first();

        $this->assertNotNull($cutting?->actual_date);
        $this->assertSame(now()->toDateString(), $cutting->actual_date->toDateString());
        $this->assertNull($workOrder->fresh('steps')->step('stitching')?->actual_date);
    }

    public function test_guest_cannot_view_work_orders_and_user_without_permission_is_forbidden(): void
    {
        $this->get(route('work-orders.index'))->assertRedirect(route('login'));

        $plain = User::factory()->create();
        $this->actingAs($plain)->get(route('work-orders.index'))->assertForbidden();
        $this->actingAs($plain)->get(route('time-and-action.index'))->assertForbidden();
    }

    public function test_updating_target_date_rebuilds_planned_tna(): void
    {
        $style = $this->style();
        $workOrder = $this->makeWorkOrder($style, ['target_date' => '2026-10-31']);

        $this->actingAs($this->user)
            ->put(route('work-orders.update', $workOrder), [
                'wo_date'          => $workOrder->wo_date->toDateString(),
                'garment_style_id' => $style->id,
                'total_qty'        => $workOrder->total_qty,
                'target_date'      => '2026-11-15',
            ])
            ->assertRedirect(route('work-orders.show', $workOrder));

        $this->assertSame('2026-11-15', $workOrder->fresh('steps')->step('dispatch')?->planned_date->toDateString());
        $this->assertSame('2026-10-21', $workOrder->fresh('steps')->step('fabric_inward')?->planned_date->toDateString());
    }

    public function test_cannot_release_work_order_without_approved_style_costing(): void
    {
        $style = GarmentStyle::create([
            'style_number' => 'ST-WO-NOCOST',
            'name'         => 'Unsigned Tee',
            'status'       => 'Active',
            'target_qty'   => 100,
        ]);
        $workOrder = $this->makeWorkOrder($style);

        $this->actingAs($this->user)
            ->from(route('work-orders.show', $workOrder))
            ->post(route('work-orders.release', $workOrder))
            ->assertRedirect(route('work-orders.show', $workOrder))
            ->assertSessionHasErrors('garment_style_id')
            ->assertSessionHas('warning');

        $this->assertSame('draft', $workOrder->fresh()->status);
    }

    private function style(): GarmentStyle
    {
        $style = GarmentStyle::create([
            'style_number' => 'ST-WO-'.uniqid(),
            'name'         => 'Work Order Tee',
            'status'       => 'Active',
            'target_qty'   => 500,
        ]);
        $this->approveStyleCosting($style);

        return $style;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeWorkOrder(GarmentStyle $style, array $overrides = []): WorkOrder
    {
        return $this->workOrders->create(array_merge([
            'wo_date'          => now()->toDateString(),
            'garment_style_id' => $style->id,
            'total_qty'        => 500,
            'target_date'      => now()->addDays(30)->toDateString(),
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function productionPayload(GarmentStyle $style, ?WorkOrder $workOrder = null, array $overrides = []): array
    {
        $payload = [
            'order_number'     => 'PO-WO-'.uniqid(),
            'garment_style_id' => $style->id,
            'total_qty'        => $workOrder?->total_qty ?? 500,
            'target_date'      => ($workOrder?->target_date ?? now()->addDays(30))->format('Y-m-d'),
            'job_work_type'    => 'in_house',
        ];

        if ($workOrder) {
            $payload['work_order_id'] = $workOrder->id;
        }

        return array_merge($payload, $overrides);
    }
}
