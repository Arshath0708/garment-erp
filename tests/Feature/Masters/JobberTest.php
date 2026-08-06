<?php

namespace Tests\Feature\Masters;

use App\Models\Agent;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use App\Models\Supplier;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class JobberTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['jobber.view', 'jobber.create', 'jobber.edit', 'jobber.delete', 'supplier.view', 'supplier.create', 'supplier.edit', 'supplier.delete'] as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo(['jobber.view', 'jobber.create', 'jobber.edit', 'jobber.delete', 'supplier.view', 'supplier.create', 'supplier.edit', 'supplier.delete']);
    }

    public function test_can_list_jobbers_only(): void
    {
        Supplier::create([
            'display_code' => 'JOB01',
            'party_type' => 'jobber',
            'company_name' => 'Jobber One',
            'status' => 'active',
        ]);

        Supplier::create([
            'display_code' => 'SUP01',
            'party_type' => 'supplier',
            'company_name' => 'Supplier One',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->get(route('masters.jobbers.index'));

        $response->assertOk()
            ->assertSee('Jobber One')
            ->assertDontSee('Supplier One');
    }

    public function test_can_create_jobber(): void
    {
        $country = Country::create(['iso_code' => 'IN', 'name' => 'India']);
        $state   = State::create(['country_id' => $country->id, 'name' => 'Tamil Nadu']);
        $city    = City::create(['state_id' => $state->id, 'name' => 'Tirupur']);
        $category = Category::forceCreate(['code' => 'CAT01', 'name' => 'Apparel']);

        $response = $this->actingAs($this->admin)->post(route('masters.jobbers.store'), [
            'display_code' => 'JOB02',
            'party_type'   => 'jobber',
            'company_name' => 'Test Jobber Co',
            'category_ids' => [$category->id],
            'country_id'   => $country->id,
            'state_id'     => $state->id,
            'city_id'      => $city->id,
            'primary_contact' => [
                'name'   => 'Primary Person',
                'mobile' => '9876543210',
            ],
            'status'                  => 'active',
            'we_supply_material'      => 1,
            'requires_sample_approval'=> 1,
            'default_delivery_mode'   => 'direct_to_port',
            'comments'                => 'Test jobber comments',
        ]);

        $response->assertRedirect(route('masters.jobbers.index'));
        $this->assertDatabaseHas('suppliers', [
            'display_code' => 'JOB02',
            'company_name' => 'Test Jobber Co',
            'party_type'   => 'jobber',
            'comments'     => 'Test jobber comments',
        ]);
    }

    public function test_can_update_jobber(): void
    {
        $country = Country::create(['iso_code' => 'IN', 'name' => 'India']);
        $state   = State::create(['country_id' => $country->id, 'name' => 'Tamil Nadu']);
        $city    = City::create(['state_id' => $state->id, 'name' => 'Tirupur']);
        $category = Category::forceCreate(['code' => 'CAT01', 'name' => 'Apparel']);

        $jobber = Supplier::create([
            'display_code' => 'JOB01',
            'party_type'   => 'jobber',
            'company_name' => 'Original Jobber Name',
            'status'       => 'active',
        ]);

        $response = $this->actingAs($this->admin)->put(route('masters.jobbers.update', $jobber), [
            'display_code' => 'JOB01',
            'party_type'   => 'jobber',
            'company_name' => 'Updated Jobber Name',
            'category_ids' => [$category->id],
            'country_id'   => $country->id,
            'state_id'     => $state->id,
            'city_id'      => $city->id,
            'primary_contact' => [
                'name'   => 'Primary Person',
                'mobile' => '9876543210',
            ],
            'status'                  => 'active',
            'we_supply_material'      => 1,
            'requires_sample_approval'=> 0,
            'default_delivery_mode'   => 'direct_to_port',
            'comments'                => 'Updated jobber comments',
        ]);

        $response->assertRedirect(route('masters.jobbers.index'));
        $this->assertDatabaseHas('suppliers', [
            'id'           => $jobber->id,
            'company_name' => 'Updated Jobber Name',
            'comments'     => 'Updated jobber comments',
        ]);
    }
}
