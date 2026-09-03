<?php

namespace Tests\Feature\Masters;

use App\Models\Category;
use App\Models\GarmentStyle;
use App\Models\Product;
use App\Models\StyleCosting;
use App\Models\User;
use App\Support\FinancialYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StyleCostingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'style-costing.view',
            'style-costing.create',
            'style-costing.edit',
            'style-costing.delete',
            'style-costing.approve',
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $this->user = User::factory()->create();
        $this->user->givePermissionTo([
            'style-costing.view',
            'style-costing.create',
            'style-costing.edit',
            'style-costing.delete',
            'style-costing.approve',
        ]);
    }

    public function test_create_form_loads_bom_lines_from_style(): void
    {
        [$style, $fabric] = $this->styleWithBom();

        $this->actingAs($this->user)
            ->get(route('style-costings.create', ['style_id' => $style->id]))
            ->assertOk()
            ->assertSee($fabric->name, false)
            ->assertSee('Cut-make', false);
    }

    public function test_creating_from_bom_copies_lines_and_totals_qty_times_rate_plus_cm(): void
    {
        [$style, $fabric] = $this->styleWithBom(1.5);

        $this->actingAs($this->user)
            ->post(route('style-costings.store'), [
                'costing_date'     => '2026-09-03',
                'garment_style_id' => $style->id,
                'cm_cost'          => 25,
                'other_cost'       => 5,
                'lines'            => [
                    [
                        'product_id'  => $fabric->id,
                        'description' => $fabric->name,
                        'item_kind'   => 'fabric',
                        'qty_per_pc'  => 1.5,
                        'unit'        => 'kg',
                        'rate'        => 80,
                    ],
                ],
            ])
            ->assertRedirect();

        $costing = StyleCosting::query()->first();
        $this->assertNotNull($costing);
        $this->assertSame('draft', $costing->status);
        $this->assertSame('CS/'.FinancialYear::current().'/001', $costing->costing_num);
        $this->assertCount(1, $costing->lines);
        $this->assertEquals(120.0, (float) $costing->material_cost);
        $this->assertEquals(150.0, (float) $costing->total_cost_per_pc);
        $this->assertSame($fabric->id, $costing->lines->first()->product_id);
    }

    public function test_approve_locks_edit_and_delete(): void
    {
        [$style, $fabric] = $this->styleWithBom();
        $costing = $this->storeDraft($style, $fabric);

        $this->actingAs($this->user)
            ->post(route('style-costings.approve', $costing))
            ->assertRedirect();

        $costing->refresh();
        $this->assertTrue($costing->isApproved());
        $this->assertNotNull($costing->approved_at);

        $this->actingAs($this->user)
            ->get(route('style-costings.edit', $costing))
            ->assertRedirect(route('style-costings.show', $costing));

        $this->actingAs($this->user)
            ->put(route('style-costings.update', $costing), [
                'costing_date'     => $costing->costing_date->toDateString(),
                'garment_style_id' => $style->id,
                'cm_cost'          => 999,
                'other_cost'       => 0,
                'lines'            => [[
                    'product_id'  => $fabric->id,
                    'description' => $fabric->name,
                    'qty_per_pc'  => 1,
                    'unit'        => 'kg',
                    'rate'        => 1,
                ]],
            ])
            ->assertRedirect()
            ->assertSessionHas('warning');

        $this->assertEquals(105.0, (float) $costing->fresh()->total_cost_per_pc);

        $this->actingAs($this->user)
            ->delete(route('style-costings.destroy', $costing))
            ->assertRedirect()
            ->assertSessionHas('warning');

        $this->assertDatabaseHas('style_costings', ['id' => $costing->id, 'status' => 'approved']);
    }

    public function test_empty_bom_and_zero_cm_is_rejected(): void
    {
        $style = GarmentStyle::create([
            'style_number' => 'ST-CS-EMPTY',
            'name'         => 'No BOM Tee',
            'status'       => 'Active',
            'target_qty'   => 100,
        ]);

        $this->actingAs($this->user)
            ->from(route('style-costings.create', ['style_id' => $style->id]))
            ->post(route('style-costings.store'), [
                'costing_date'     => '2026-09-03',
                'garment_style_id' => $style->id,
                'cm_cost'          => 0,
                'other_cost'       => 0,
            ])
            ->assertRedirect(route('style-costings.create', ['style_id' => $style->id]))
            ->assertSessionHasErrors('garment_style_id');

        $this->assertDatabaseCount('style_costings', 0);
    }

    public function test_approved_rupee_shows_on_style_and_bom(): void
    {
        [$style, $fabric] = $this->styleWithBom();
        $costing = $this->storeDraft($style, $fabric);

        $this->actingAs($this->user)
            ->post(route('style-costings.approve', $costing))
            ->assertRedirect();

        $this->actingAs($this->user)
            ->get(route('masters.styles.show', $style))
            ->assertOk()
            ->assertSee('105.00', false)
            ->assertSee($costing->costing_num, false)
            ->assertSee('New costing', false);

        $this->actingAs($this->user)
            ->get(route('masters.bom.index'))
            ->assertOk()
            ->assertSee('105.00', false)
            ->assertSee('Cost this style', false);
    }

    public function test_empty_bom_is_allowed_when_cm_is_entered(): void
    {
        $style = GarmentStyle::create([
            'style_number' => 'ST-CS-CM',
            'name'         => 'CM only Tee',
            'status'       => 'Active',
            'target_qty'   => 100,
        ]);

        $this->actingAs($this->user)
            ->post(route('style-costings.store'), [
                'costing_date'     => '2026-09-03',
                'garment_style_id' => $style->id,
                'cm_cost'          => 45,
                'other_cost'       => 0,
            ])
            ->assertRedirect();

        $costing = StyleCosting::query()->first();
        $this->assertNotNull($costing);
        $this->assertEquals(45.0, (float) $costing->total_cost_per_pc);
        $this->assertCount(0, $costing->lines);
    }

    public function test_guest_cannot_view_costings_and_user_without_permission_is_forbidden(): void
    {
        $this->get(route('style-costings.index'))->assertRedirect(route('login'));

        $plain = User::factory()->create();
        $this->actingAs($plain)->get(route('style-costings.index'))->assertForbidden();
        $this->actingAs($plain)->get(route('style-costings.create'))->assertForbidden();
    }

    /**
     * @return array{0: GarmentStyle, 1: Product}
     */
    private function styleWithBom(float $qtyPerPc = 1.0): array
    {
        $category = Category::forceCreate([
            'code'   => 'FAB-CS',
            'name'   => 'Costing fabric',
            'status' => 'active',
        ]);
        $fabric = Product::create([
            'category_id'     => $category->id,
            'item_group_code' => 'CS'.substr(uniqid(), -6),
            'name'            => 'Costing Cotton '.uniqid(),
            'status'          => 'active',
            'item_kind'       => 'fabric',
            'qty_on_hand'     => 0,
            'unit_po'         => 'kg',
        ]);
        $style = GarmentStyle::create([
            'style_number' => 'ST-CS-'.uniqid(),
            'name'         => 'Costed Tee',
            'status'       => 'Active',
            'target_qty'   => 500,
        ]);
        $style->materials()->create([
            'product_id' => $fabric->id,
            'qty_per_pc' => $qtyPerPc,
            'unit'       => 'kg',
            'sort_order' => 0,
        ]);

        return [$style, $fabric];
    }

    private function storeDraft(GarmentStyle $style, Product $fabric): StyleCosting
    {
        $this->actingAs($this->user)
            ->post(route('style-costings.store'), [
                'costing_date'     => '2026-09-03',
                'garment_style_id' => $style->id,
                'cm_cost'          => 20,
                'other_cost'       => 5,
                'lines'            => [[
                    'product_id'  => $fabric->id,
                    'description' => $fabric->name,
                    'item_kind'   => 'fabric',
                    'qty_per_pc'  => 1,
                    'unit'        => 'kg',
                    'rate'        => 80,
                ]],
            ])
            ->assertRedirect();

        $costing = StyleCosting::query()->latest('id')->first();
        $this->assertNotNull($costing);

        return $costing;
    }
}
