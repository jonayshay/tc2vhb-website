<?php

namespace Tests\Feature;

use App\Models\Partner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PartenairesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_returns_200_with_inertia_component(): void
    {
        $response = $this->get('/partenaires');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) =>
            $page->component('Partenaires')
        );
    }

    public function test_partenaires_are_sorted_by_sort_order_asc(): void
    {
        $third = Partner::factory()->create(['name' => 'C', 'sort_order' => 3]);
        $first = Partner::factory()->create(['name' => 'A', 'sort_order' => 1]);
        $second = Partner::factory()->create(['name' => 'B', 'sort_order' => 2]);

        $response = $this->get('/partenaires');

        $response->assertInertia(fn (Assert $page) =>
            $page->component('Partenaires')
                ->where('partenaires.0.id', $first->id)
                ->where('partenaires.1.id', $second->id)
                ->where('partenaires.2.id', $third->id)
        );
    }

    public function test_page_works_with_no_partners(): void
    {
        $response = $this->get('/partenaires');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) =>
            $page->component('Partenaires')
                ->where('partenaires', [])
        );
    }
}
