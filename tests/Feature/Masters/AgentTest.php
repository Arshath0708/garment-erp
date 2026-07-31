<?php

namespace Tests\Feature\Masters;

use App\Models\Agent;
use App\Models\CalculationBasis;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsRole(string $role): User
    {
        $user = User::factory()->create(['status' => true]);
        $user->assignRole($role);
        return $user;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'agent_type'   => 'supplier',
            'name'         => 'Test Agent Name',
            'display_code' => 'AGT01',
            'status'       => 'active',
        ], $overrides);
    }

    /*
    |--------------------------------------------------------------------------
    | Permissions & Routing
    |--------------------------------------------------------------------------
    */

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('masters.agents.index'))->assertRedirect(route('login'));
    }

    public function test_a_role_without_agent_view_is_denied(): void
    {
        $user = $this->actingAsRole('Jobworker'); // No agent permissions

        $this->actingAs($user)->get(route('masters.agents.index'))->assertStatus(403);
    }

    public function test_a_view_only_role_cannot_create_or_delete(): void
    {
        // Give a user view-only access
        $user = $this->actingAsRole('Super Admin'); // Setup role via Spatie
        $viewUser = User::factory()->create(['status' => true]);
        $viewUser->assignRole('Merchandising & Manufacturing'); // Has agent.view but not agent.create/delete

        $this->actingAs($viewUser)->get(route('masters.agents.create'))->assertStatus(403);
        $this->actingAs($viewUser)->post(route('masters.agents.store'), $this->payload())->assertStatus(403);
    }

    /*
    |--------------------------------------------------------------------------
    | CRUD & Validation
    |--------------------------------------------------------------------------
    */

    public function test_an_agent_can_be_created_with_categories_and_basis(): void
    {
        $user = $this->actingAsRole('Super Admin');
        $basis = CalculationBasis::first();
        $cat1 = Category::forceCreate(['code' => 'CAT91', 'name' => 'Category 91', 'status' => 'active']);
        $cat2 = Category::forceCreate(['code' => 'CAT92', 'name' => 'Category 92', 'status' => 'active']);
        $categories = collect([$cat1, $cat2]);

        $response = $this->actingAs($user)->post(route('masters.agents.store'), $this->payload([
            'calculation_basis_id' => $basis->id,
            'commission_rate'      => 4.50,
            'categories'           => $categories->pluck('id')->all(),
        ]));

        $response->assertRedirect(route('masters.agents.index'));

        $this->assertDatabaseHas('agents', [
            'display_code'         => 'AGT01',
            'name'                 => 'Test Agent Name',
            'agent_type'           => 'supplier',
            'calculation_basis_id' => $basis->id,
            'commission_rate'      => 4.50,
        ]);

        $agent = Agent::where('display_code', 'AGT01')->first();
        $this->assertCount(2, $agent->categories);
    }

    public function test_agent_display_code_must_be_alphanumeric_and_max_5(): void
    {
        $user = $this->actingAsRole('Super Admin');

        // Regex fail (has hyphen)
        $this->actingAs($user)
            ->post(route('masters.agents.store'), $this->payload(['display_code' => 'AG-01']))
            ->assertSessionHasErrors('display_code');

        // Length fail (6 chars)
        $this->actingAs($user)
            ->post(route('masters.agents.store'), $this->payload(['display_code' => 'AGT001']))
            ->assertSessionHasErrors('display_code');
    }

    public function test_duplicate_display_code_is_rejected(): void
    {
        $user = $this->actingAsRole('Super Admin');
        Agent::create($this->payload());

        $this->actingAs($user)
            ->post(route('masters.agents.store'), $this->payload(['name' => 'Other Name']))
            ->assertSessionHasErrors('display_code');
    }

    public function test_agent_can_be_updated(): void
    {
        $user = $this->actingAsRole('Super Admin');
        $agent = Agent::create($this->payload());

        $response = $this->actingAs($user)->put(route('masters.agents.update', $agent), $this->payload([
            'name' => 'Updated Agent Name',
        ]));

        $response->assertRedirect(route('masters.agents.index'));
        $this->assertSame('Updated Agent Name', $agent->refresh()->name);
    }

    public function test_agent_can_be_soft_deleted(): void
    {
        $user = $this->actingAsRole('Super Admin');
        $agent = Agent::create($this->payload());

        $this->actingAs($user)->delete(route('masters.agents.destroy', $agent));

        $this->assertSoftDeleted('agents', ['id' => $agent->id]);
    }

    public function test_code_availability_reports_correctly(): void
    {
        $user = $this->actingAsRole('Super Admin');
        $agent = Agent::create($this->payload());

        // Active code taken
        $this->actingAs($user)
            ->getJson(route('masters.agents.check-code', ['field' => 'display_code', 'value' => 'AGT01']))
            ->assertOk()->assertJson(['available' => false]);

        // Ignored during edit
        $this->actingAs($user)
            ->getJson(route('masters.agents.check-code', ['field' => 'display_code', 'value' => 'AGT01', 'ignore' => $agent->id]))
            ->assertOk()->assertJson(['available' => true]);

        // Soft deleted code also counts as taken
        $agent->delete();
        $this->actingAs($user)
            ->getJson(route('masters.agents.check-code', ['field' => 'display_code', 'value' => 'AGT01']))
            ->assertOk()->assertJson(['available' => false]);
    }
}
