<?php

namespace Tests\Feature\Manufacturing;

use App\Models\GarmentStyle;
use App\Models\ProductionLine;
use App\Models\ProductionOrder;
use App\Models\User;
use App\Services\Manufacturing\WorkOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FloorScanTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ProductionLine $line;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['work-order.view', 'work-order.edit', 'work-order.create', 'work-order.approve'] as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $this->user = User::factory()->create();
        $this->user->givePermissionTo(['work-order.view', 'work-order.edit', 'work-order.create', 'work-order.approve']);

        $this->line = ProductionLine::query()->where('name', 'Line 1')->firstOrFail();
    }

    public function test_phone_scan_logs_pcs_and_bumps_stitching(): void
    {
        $order = $this->makeOrder('PO-SCAN-1', 10);

        $this->actingAs($this->user)
            ->get(route('floor.scan'))
            ->assertOk()
            ->assertSee('Phone scan', false);

        $this->actingAs($this->user)
            ->from(route('floor.scan'))
            ->post(route('floor.scan.store'), [
                'production_line_id' => $this->line->id,
                'code' => 'po-scan-1',
                'pcs' => 3,
            ])
            ->assertRedirect(route('floor.scan'))
            ->assertSessionHas('success');

        $this->assertSame(3, (int) $order->fresh()->stitching_qty);
        $this->assertSame('Stitching', $order->fresh()->current_stage);
        $this->assertDatabaseHas('production_line_outputs', [
            'production_order_id' => $order->id,
            'pcs' => 3,
            'source' => 'scan',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_work_order_number_finds_the_production_order(): void
    {
        $style = GarmentStyle::create([
            'style_number' => 'ST-SCAN-WO',
            'name' => 'Scan Tee',
            'status' => 'Active',
            'target_qty' => 20,
        ]);
        $this->approveStyleCosting($style);

        $workOrders = app(WorkOrderService::class);
        $workOrder = $workOrders->release($workOrders->create([
            'wo_date' => now()->toDateString(),
            'garment_style_id' => $style->id,
            'total_qty' => 20,
            'target_date' => now()->addDays(20)->toDateString(),
        ]));

        $order = $this->makeOrder('PO-SCAN-WO', 20, [
            'work_order_id' => $workOrder->id,
            'garment_style_id' => $style->id,
        ]);

        $this->actingAs($this->user)
            ->post(route('floor.scan.store'), [
                'production_line_id' => $this->line->id,
                'code' => $workOrder->wo_num,
                'pcs' => 2,
            ])
            ->assertSessionHas('success');

        $this->assertSame(2, (int) $order->fresh()->stitching_qty);
    }

    public function test_unknown_code_does_not_log(): void
    {
        $this->actingAs($this->user)
            ->from(route('floor.scan'))
            ->post(route('floor.scan.store'), [
                'production_line_id' => $this->line->id,
                'code' => 'NO-SUCH',
                'pcs' => 1,
            ])
            ->assertRedirect(route('floor.scan'))
            ->assertSessionHas('warning');

        $this->assertDatabaseCount('production_line_outputs', 0);
    }

    public function test_cannot_scan_more_than_remaining_qty(): void
    {
        $order = $this->makeOrder('PO-SCAN-FULL', 5, ['stitching_qty' => 5]);

        $this->actingAs($this->user)
            ->from(route('floor.scan'))
            ->post(route('floor.scan.store'), [
                'production_line_id' => $this->line->id,
                'code' => $order->order_number,
                'pcs' => 1,
            ])
            ->assertSessionHas('warning');

        $this->assertDatabaseCount('production_line_outputs', 0);
    }

    public function test_bundle_ticket_prints_the_order_number(): void
    {
        $order = $this->makeOrder('PO-TICKET-1', 8);

        $this->actingAs($this->user)
            ->get(route('manufacturing.bundle-ticket', $order))
            ->assertOk()
            ->assertSee('PO-TICKET-1', false)
            ->assertSee('Sewing line bundle', false);
    }

    public function test_guest_and_user_without_permission_are_blocked(): void
    {
        $this->get(route('floor.scan'))->assertRedirect(route('login'));

        $plain = User::factory()->create();
        $this->actingAs($plain)->get(route('floor.scan'))->assertForbidden();
        $this->actingAs($plain)
            ->post(route('floor.scan.store'), [
                'production_line_id' => $this->line->id,
                'code' => 'X',
                'pcs' => 1,
            ])
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeOrder(string $number, int $qty, array $overrides = []): ProductionOrder
    {
        $style = GarmentStyle::query()->find($overrides['garment_style_id'] ?? null)
            ?? GarmentStyle::create([
                'style_number' => 'ST-'.$number,
                'name' => 'Scan style',
                'status' => 'Active',
                'target_qty' => $qty,
            ]);

        return ProductionOrder::create(array_merge([
            'order_number' => $number,
            'garment_style_id' => $style->id,
            'total_qty' => $qty,
            'target_date' => now()->addDays(10),
            'current_stage' => 'Cutting',
            'status' => 'In Progress',
        ], $overrides));
    }
}
