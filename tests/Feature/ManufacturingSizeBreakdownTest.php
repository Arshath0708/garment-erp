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
                'damage'           => [
                    'cutting' => 5,
                ],
            ])
            ->assertRedirect(route('manufacturing.index'));

        $order->refresh();
        $this->assertSame(500, $order->cutting_qty);
        $this->assertSame(100, $order->sizeQty('cutting', 'M'));
        $this->assertSame(5, $order->stageDamage('cutting'));
        $this->assertSame(495, $order->stageGoodQty('cutting'));
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

    public function test_filled_stages_and_challan_include_printing_not_only_cutting(): void
    {
        $user = User::factory()->create();
        $style = GarmentStyle::create([
            'style_number' => 'ST-SIZE-6',
            'name'         => 'tshirt',
            'status'       => 'Active',
            'target_qty'   => 50,
        ]);
        $order = ProductionOrder::create([
            'order_number'     => 'PO-SIZE-6',
            'garment_style_id' => $style->id,
            'total_qty'        => 50,
            'target_date'      => now()->addDays(10),
            'current_stage'    => 'Cutting',
            'status'           => 'In Progress',
            'job_work_type'    => 'in_house',
        ]);

        $this->actingAs($user)
            ->post(route('manufacturing.update-stage', $order), [
                'current_stage' => 'Printing',
                'sizes'         => [
                    'cutting'  => ['S' => 12, 'M' => 8, 'L' => 11, 'XL' => 16],
                    'printing' => ['S' => 10, 'M' => 7, 'L' => 10, 'XL' => 12],
                ],
                'damage' => [
                    'cutting'  => 3,
                    'printing' => 1,
                ],
            ])
            ->assertRedirect();

        $order->refresh();
        $this->assertSame(47, $order->cutting_qty);
        $this->assertSame(39, $order->printing_qty);
        $this->assertSame('Printing', $order->current_stage);
        $this->assertSame('printing', $order->currentStageKey());

        $keys = array_column($order->filledStageRows(), 'key');
        $this->assertSame(['cutting', 'printing'], $keys);

        $this->actingAs($user)
            ->get(route('manufacturing.index'))
            ->assertOk()
            ->assertSee('Stage sizes')
            ->assertDontSee('Cutting sizes');

        $html = view('manufacturing.job-work-challan', [
            'order'     => $order->load(['garmentStyle', 'buyer', 'jobber']),
            'company'   => (object) [
                'company_name' => 'Guru Traders',
                'address'      => 'Test',
                'tagline'      => null,
                'phone'        => null,
            ],
            'sizes'     => ProductionOrder::SIZES,
            'stageRows' => $order->filledStageRows(),
        ])->render();

        $this->assertStringContainsString('Cutting', $html);
        $this->assertStringContainsString('Printing / Embroidery', $html);
        $this->assertStringContainsString('>39<', $html);
        $this->assertStringContainsString('>47<', $html);
    }

    public function test_stitching_qty_cannot_exceed_cutting_good_pcs(): void
    {
        $user = User::factory()->create();
        $style = GarmentStyle::create([
            'style_number' => 'ST-SIZE-3',
            'name'         => 'Flow Tee',
            'status'       => 'Active',
            'target_qty'   => 100,
        ]);
        $order = ProductionOrder::create([
            'order_number'     => 'PO-SIZE-3',
            'garment_style_id' => $style->id,
            'total_qty'        => 100,
            'target_date'      => now()->addDays(10),
            'current_stage'    => 'Cutting',
            'status'           => 'In Progress',
        ]);

        $this->actingAs($user)
            ->from(route('manufacturing.edit', $order))
            ->put(route('manufacturing.update', $order), [
                'order_number'     => 'PO-SIZE-3',
                'garment_style_id' => $style->id,
                'total_qty'        => 100,
                'target_date'      => now()->addDays(10)->format('Y-m-d'),
                'current_stage'    => 'Stitching',
                'status'           => 'In Progress',
                'job_work_type'    => 'in_house',
                'sizes'            => [
                    'cutting'   => ['S' => 40, 'M' => 40, 'L' => 20],
                    'stitching' => ['S' => 50, 'M' => 50, 'L' => 10],
                ],
                'damage' => [
                    'cutting' => 10,
                ],
            ])
            ->assertRedirect(route('manufacturing.edit', $order))
            ->assertSessionHasErrors(['sizes.stitching', 'sizes.stitching.S']);
    }

    public function test_same_size_cannot_exceed_previous_stage(): void
    {
        $user = User::factory()->create();
        $style = GarmentStyle::create([
            'style_number' => 'ST-SIZE-5',
            'name'         => 'Size Cap Tee',
            'status'       => 'Active',
            'target_qty'   => 50,
        ]);
        $order = ProductionOrder::create([
            'order_number'     => 'PO-SIZE-5',
            'garment_style_id' => $style->id,
            'total_qty'        => 50,
            'target_date'      => now()->addDays(10),
            'current_stage'    => 'Cutting',
            'status'           => 'In Progress',
        ]);

        $this->actingAs($user)
            ->from(route('manufacturing.edit', $order))
            ->put(route('manufacturing.update', $order), [
                'order_number'     => 'PO-SIZE-5',
                'garment_style_id' => $style->id,
                'total_qty'        => 50,
                'target_date'      => now()->addDays(10)->format('Y-m-d'),
                'current_stage'    => 'Printing',
                'status'           => 'In Progress',
                'job_work_type'    => 'in_house',
                'sizes'            => [
                    'cutting'  => ['S' => 12, 'M' => 8, 'L' => 11, 'XL' => 16],
                    'printing' => ['S' => 15, 'M' => 8, 'L' => 11, 'XL' => 10],
                ],
            ])
            ->assertRedirect(route('manufacturing.edit', $order))
            ->assertSessionHasErrors('sizes.printing.S');
    }

    public function test_damage_cannot_exceed_stage_qty(): void
    {
        $user = User::factory()->create();
        $style = GarmentStyle::create([
            'style_number' => 'ST-SIZE-4',
            'name'         => 'Damage Tee',
            'status'       => 'Active',
            'target_qty'   => 50,
        ]);
        $order = ProductionOrder::create([
            'order_number'     => 'PO-SIZE-4',
            'garment_style_id' => $style->id,
            'total_qty'        => 50,
            'target_date'      => now()->addDays(10),
            'current_stage'    => 'Cutting',
            'status'           => 'In Progress',
        ]);

        $this->actingAs($user)
            ->from(route('manufacturing.edit', $order))
            ->put(route('manufacturing.update', $order), [
                'order_number'     => 'PO-SIZE-4',
                'garment_style_id' => $style->id,
                'total_qty'        => 50,
                'target_date'      => now()->addDays(10)->format('Y-m-d'),
                'current_stage'    => 'Cutting',
                'status'           => 'In Progress',
                'job_work_type'    => 'in_house',
                'sizes'            => [
                    'cutting' => ['S' => 20, 'M' => 10],
                ],
                'damage' => [
                    'cutting' => 40,
                ],
            ])
            ->assertRedirect(route('manufacturing.edit', $order))
            ->assertSessionHasErrors('damage.cutting');
    }
}
