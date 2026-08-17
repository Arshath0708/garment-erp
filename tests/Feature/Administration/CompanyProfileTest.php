<?php

namespace Tests\Feature\Administration;

use App\Models\CompanyProfile;
use App\Models\User;
use Database\Seeders\CompanyProfileSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CompanyProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['company-profile.view', 'company-profile.edit'] as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo(['company-profile.view', 'company-profile.edit']);

        $this->user = User::factory()->create();
    }

    public function test_current_creates_a_singleton_row_on_first_read(): void
    {
        $this->assertSame(0, CompanyProfile::count());

        $profile = CompanyProfile::current();

        $this->assertSame(1, CompanyProfile::count());
        $this->assertSame($profile->id, CompanyProfile::current()->id);
    }

    public function test_seeder_prefills_from_the_letterhead(): void
    {
        $this->seed(CompanyProfileSeeder::class);

        $profile = CompanyProfile::current();

        $this->assertSame('Guru Traders', $profile->company_name);
        $this->assertNotEmpty($profile->address);
        $this->assertNull($profile->gstin);
    }

    public function test_admin_can_view_and_update_company_profile(): void
    {
        $this->actingAs($this->admin)
            ->get(route('user-management.company-profile.edit'))
            ->assertOk();

        $response = $this->actingAs($this->admin)->put(route('user-management.company-profile.update'), [
            'company_name' => 'Guru Traders',
            'gstin'        => '27AAAFG0259R1Z2',
            'iec_code'     => 'AAAFG0259R',
        ]);

        $response->assertRedirect(route('user-management.company-profile.edit'));

        $profile = CompanyProfile::current();
        $this->assertSame('27AAAFG0259R1Z2', $profile->gstin);
        $this->assertSame('AAAFG0259R', $profile->iec_code);
    }

    public function test_user_without_permission_cannot_view_or_update(): void
    {
        $this->actingAs($this->user)
            ->get(route('user-management.company-profile.edit'))
            ->assertForbidden();

        $this->actingAs($this->user)
            ->put(route('user-management.company-profile.update'), ['company_name' => 'Someone Else'])
            ->assertForbidden();
    }
}
