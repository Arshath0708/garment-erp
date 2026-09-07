<?php

namespace Tests\Feature\Reports;

use App\Models\GarmentStyle;
use App\Models\ProductionOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FactoryBoardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['report.view', 'report.export'] as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $this->user = User::factory()->create();
        $this->user->givePermissionTo(['report.view', 'report.export']);
    }

    public function test_factory_board_shows_style_and_floor_qty_on_one_row(): void
    {
        $style = GarmentStyle::create([
            'style_number' => 'ST-BOARD-1',
            'name' => 'Board Tee',
            'status' => 'Active',
            'target_qty' => 100,
        ]);

        ProductionOrder::create([
            'order_number' => 'PO-BOARD-1',
            'garment_style_id' => $style->id,
            'total_qty' => 100,
            'cutting_qty' => 80,
            'stitching_qty' => 40,
            'packing_qty' => 10,
            'dispatch_qty' => 5,
            'target_date' => now()->addDays(10),
            'current_stage' => 'Stitching',
            'status' => 'In Progress',
        ]);

        $this->actingAs($this->user)
            ->get(route('reports.factory-board'))
            ->assertOk()
            ->assertSee('ST-BOARD-1', false)
            ->assertSee('PO-BOARD-1', false)
            ->assertSee('80', false)
            ->assertSee('40', false)
            ->assertSee('CSV for Power BI', false);

        $this->actingAs($this->user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Factory board', false);
    }

    public function test_late_filter_hides_on_time_orders(): void
    {
        $style = GarmentStyle::create([
            'style_number' => 'ST-BOARD-2',
            'name' => 'Late Tee',
            'status' => 'Active',
            'target_qty' => 50,
        ]);

        ProductionOrder::create([
            'order_number' => 'PO-LATE-1',
            'garment_style_id' => $style->id,
            'total_qty' => 50,
            'dispatch_qty' => 0,
            'target_date' => now()->subDay(),
            'current_stage' => 'Cutting',
            'status' => 'In Progress',
        ]);

        ProductionOrder::create([
            'order_number' => 'PO-OK-1',
            'garment_style_id' => $style->id,
            'total_qty' => 50,
            'dispatch_qty' => 50,
            'target_date' => now()->subDay(),
            'current_stage' => 'Dispatch',
            'status' => 'Completed',
        ]);

        $this->actingAs($this->user)
            ->get(route('reports.factory-board', ['late' => 1]))
            ->assertOk()
            ->assertSee('PO-LATE-1', false)
            ->assertDontSee('PO-OK-1', false);
    }

    public function test_csv_export_lists_the_same_rows(): void
    {
        $style = GarmentStyle::create([
            'style_number' => 'ST-CSV-1',
            'name' => 'Csv Tee',
            'status' => 'Active',
            'target_qty' => 20,
        ]);

        ProductionOrder::create([
            'order_number' => 'PO-CSV-1',
            'garment_style_id' => $style->id,
            'total_qty' => 20,
            'cutting_qty' => 20,
            'target_date' => now()->addDays(3),
            'current_stage' => 'Cutting',
            'status' => 'In Progress',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('reports.factory-board.export'));

        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Work order', $csv);
        $this->assertStringContainsString('ST-CSV-1', $csv);
        $this->assertStringContainsString('PO-CSV-1', $csv);
    }

    public function test_guest_and_user_without_permission_are_blocked(): void
    {
        $this->get(route('reports.factory-board'))->assertRedirect(route('login'));

        $plain = User::factory()->create();
        $this->actingAs($plain)->get(route('reports.factory-board'))->assertForbidden();
        $this->actingAs($plain)->get(route('reports.factory-board.export'))->assertForbidden();
    }

    public function test_csv_requires_export_permission(): void
    {
        $viewer = User::factory()->create();
        $viewer->givePermissionTo('report.view');

        $this->actingAs($viewer)
            ->get(route('reports.factory-board'))
            ->assertOk()
            ->assertDontSee('CSV for Power BI', false);

        $this->actingAs($viewer)
            ->get(route('reports.factory-board.export'))
            ->assertForbidden();
    }
}
