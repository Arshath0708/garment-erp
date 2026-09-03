<?php

namespace Tests\Feature;

use App\Models\GarmentStyle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_search(): void
    {
        $this->getJson(route('search', ['q' => 'ST']))->assertUnauthorized();
    }

    public function test_search_returns_matching_style(): void
    {
        $user = User::factory()->create();
        $style = GarmentStyle::create([
            'style_number' => 'ST-FIND-99',
            'name'         => 'Search Tee',
            'status'       => 'Active',
        ]);

        $this->actingAs($user)
            ->getJson(route('search', ['q' => 'ST-FIND']))
            ->assertOk()
            ->assertJsonFragment([
                'group' => 'Styles',
                'label' => 'ST-FIND-99 — Search Tee',
                'url'   => route('masters.styles.show', $style),
            ]);
    }

    public function test_short_query_returns_empty_results(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('search', ['q' => 'S']))
            ->assertOk()
            ->assertExactJson(['results' => []]);
    }
}
