<?php

namespace Tests\Feature\Masters;

use App\Models\FobValue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FobValueTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['fob-value.view', 'fob-value.create', 'fob-value.edit', 'fob-value.delete'] as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo(['fob-value.view', 'fob-value.create', 'fob-value.edit', 'fob-value.delete']);
    }

    public function test_can_list_fob_values(): void
    {
        FobValue::create(['name' => 'FOB Value', 'status' => 'active']);

        $response = $this->actingAs($this->admin)->get(route('masters.fob-values.index'));

        $response->assertOk()
            ->assertSee('FOB Value');
    }

    public function test_can_create_fob_value(): void
    {
        $response = $this->actingAs($this->admin)->post(route('masters.fob-values.store'), [
            'name'    => 'Ex Factory',
            'status'  => 'active',
            'remarks' => 'Test remarks',
        ]);

        $response->assertRedirect(route('masters.fob-values.index'));
        $this->assertDatabaseHas('fob_values', [
            'name' => 'Ex Factory',
            'remarks' => 'Test remarks',
        ]);
    }

    public function test_prevents_duplicate_fob_value_name(): void
    {
        FobValue::create(['name' => 'CIF Value', 'status' => 'active']);

        $response = $this->actingAs($this->admin)->post(route('masters.fob-values.store'), [
            'name'   => 'CIF Value',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_can_toggle_fob_value_status(): void
    {
        $fobValue = FobValue::create(['name' => 'Gross Value', 'status' => 'active']);

        $response = $this->actingAs($this->admin)
            ->patch(route('masters.fob-values.toggle-status', $fobValue));

        $response->assertRedirect();
        $this->assertEquals('inactive', $fobValue->fresh()->status);
    }
}
