<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Models\OrderConfirmation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
        $response->assertSee('Total Inquiries');
        $response->assertSee('Order Confirmations');
    }

    public function test_dashboard_displays_zero_state_when_no_records_exist(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('inquiryCount', 0);
        $response->assertViewHas('orderConfirmationCount', 0);
    }

    public function test_dashboard_reflects_actual_inquiry_and_order_confirmation_counts(): void
    {
        $user = User::factory()->create();

        Inquiry::factory()->count(3)->create();
        OrderConfirmation::factory()->count(2)->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('inquiryCount', 3);
        $response->assertViewHas('orderConfirmationCount', 2);
    }

    public function test_dashboard_does_not_count_soft_deleted_records(): void
    {
        $user = User::factory()->create();

        $activeInquiry = Inquiry::factory()->create();
        $deletedInquiry = Inquiry::factory()->create();
        $deletedInquiry->delete();

        $activeOc = OrderConfirmation::factory()->create();
        $deletedOc = OrderConfirmation::factory()->create();
        $deletedOc->delete();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('inquiryCount', 1);
        $response->assertViewHas('orderConfirmationCount', 1);
    }
}
