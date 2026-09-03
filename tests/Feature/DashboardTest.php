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
        $response->assertSee(route('manufacturing.index', ['stage' => 'Printing']), false);
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
        $buyer = \App\Models\Buyer::forceCreate(['display_code' => 'B01', 'company_name' => 'Buyer One']);
        $cat = \App\Models\Category::forceCreate(['code' => 'C01', 'name' => 'Cat 1', 'status' => 'active']);
        $df = \App\Models\DocumentFormat::forceCreate(['name' => 'Standard']);
        $cur = \App\Models\Currency::forceCreate(['name' => 'INR', 'iso_code' => 'INR', 'symbol' => '₹']);

        Inquiry::forceCreate(['inquiry_no' => 'INQ-001', 'financial_year' => '2026', 'buyer_id' => $buyer->id, 'category_id' => $cat->id, 'document_format_id' => $df->id, 'currency_id' => $cur->id, 'inquiry_date' => now()]);
        Inquiry::forceCreate(['inquiry_no' => 'INQ-002', 'financial_year' => '2026', 'buyer_id' => $buyer->id, 'category_id' => $cat->id, 'document_format_id' => $df->id, 'currency_id' => $cur->id, 'inquiry_date' => now()]);
        Inquiry::forceCreate(['inquiry_no' => 'INQ-003', 'financial_year' => '2026', 'buyer_id' => $buyer->id, 'category_id' => $cat->id, 'document_format_id' => $df->id, 'currency_id' => $cur->id, 'inquiry_date' => now()]);

        OrderConfirmation::forceCreate(['oc_num' => 'OC-001', 'financial_year' => '2026', 'buyer_id' => $buyer->id, 'category_id' => $cat->id, 'document_format_id' => $df->id, 'currency_id' => $cur->id, 'oc_date' => now()]);
        OrderConfirmation::forceCreate(['oc_num' => 'OC-002', 'financial_year' => '2026', 'buyer_id' => $buyer->id, 'category_id' => $cat->id, 'document_format_id' => $df->id, 'currency_id' => $cur->id, 'oc_date' => now()]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('inquiryCount', 3);
        $response->assertViewHas('orderConfirmationCount', 2);
    }

    public function test_dashboard_does_not_count_soft_deleted_records(): void
    {
        $user = User::factory()->create();
        $buyer = \App\Models\Buyer::forceCreate(['display_code' => 'B02', 'company_name' => 'Buyer Two']);
        $cat = \App\Models\Category::forceCreate(['code' => 'C02', 'name' => 'Cat 2', 'status' => 'active']);
        $df = \App\Models\DocumentFormat::forceCreate(['name' => 'Standard2']);
        $cur = \App\Models\Currency::forceCreate(['name' => 'USD', 'iso_code' => 'USD', 'symbol' => '$']);

        $activeInquiry = Inquiry::forceCreate(['inquiry_no' => 'INQ-010', 'financial_year' => '2026', 'buyer_id' => $buyer->id, 'category_id' => $cat->id, 'document_format_id' => $df->id, 'currency_id' => $cur->id, 'inquiry_date' => now()]);
        $deletedInquiry = Inquiry::forceCreate(['inquiry_no' => 'INQ-011', 'financial_year' => '2026', 'buyer_id' => $buyer->id, 'category_id' => $cat->id, 'document_format_id' => $df->id, 'currency_id' => $cur->id, 'inquiry_date' => now()]);
        $deletedInquiry->delete();

        $activeOc = OrderConfirmation::forceCreate(['oc_num' => 'OC-010', 'financial_year' => '2026', 'buyer_id' => $buyer->id, 'category_id' => $cat->id, 'document_format_id' => $df->id, 'currency_id' => $cur->id, 'oc_date' => now()]);
        $deletedOc = OrderConfirmation::forceCreate(['oc_num' => 'OC-011', 'financial_year' => '2026', 'buyer_id' => $buyer->id, 'category_id' => $cat->id, 'document_format_id' => $df->id, 'currency_id' => $cur->id, 'oc_date' => now()]);
        $deletedOc->delete();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('inquiryCount', 1);
        $response->assertViewHas('orderConfirmationCount', 1);
    }

    public function test_dashboard_stage_cards_keep_the_clicked_manufacturing_step(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee(route('manufacturing.index', ['stage' => 'Cutting']), false)
            ->assertSee(route('manufacturing.index', ['stage' => 'Stitching']), false);

        $this->actingAs($user)
            ->get(route('manufacturing.index', ['stage' => 'Printing']))
            ->assertOk()
            ->assertSee('data-pipeline-stage="Printing"', false);
    }
}
