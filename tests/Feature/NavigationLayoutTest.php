<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Breadcrumbs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_home_breadcrumb_and_sidebar_search(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Search menu', false)
            ->assertSee('erp-breadcrumb', false)
            ->assertSee('bi-arrow-left', false)
            ->assertDontSee('sidebar-menu-toggle', false)
            ->assertDontSee('nav-arrow bi bi-chevron-right', false)
            ->assertSee('Goods Inward', false)
            ->assertSee('Fabric &amp; Trims PO', false)
            ->assertSee('FOB Values', false)
            ->assertSee('Work Orders', false)
            ->assertSee('Time &amp; Action', false)
            ->assertSee('Style Costing', false)
            ->assertSee('Job Work Issue', false)
            ->assertSee('Line efficiency', false)
            ->assertSee('Find order, style, PO', false)
            ->assertSee('Tally', false);
    }

    public function test_inward_index_breadcrumb_is_home_module_screen(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('procurement.inward-entries.index'));

        $trail = collect(Breadcrumbs::trail())->pluck('label')->all();

        $this->assertSame(['Home', 'Inventory & Job Work', 'Goods Inward'], $trail);
    }

    public function test_job_work_index_breadcrumb_is_home_module_screen(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('job-work.index'));

        $trail = collect(Breadcrumbs::trail())->pluck('label')->all();

        $this->assertSame(['Home', 'Inventory & Job Work', 'Job Work Issue / Receive'], $trail);
    }

    public function test_style_edit_adds_edit_action_crumb(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('masters.styles.create'));

        $trail = collect(Breadcrumbs::trail())->pluck('label')->all();

        $this->assertSame(['Home', 'Masters', 'Style Master & Tech Pack', 'New'], $trail);
    }
}
