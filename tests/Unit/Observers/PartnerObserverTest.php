<?php

namespace Tests\Unit\Observers;

use App\Models\Partner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_sort_order_is_auto_incremented_on_creation(): void
    {
        Partner::factory()->create(['sort_order' => 1]);
        Partner::factory()->create(['sort_order' => 2]);

        $partner = Partner::create(['name' => 'Nouveau Partenaire']);

        $this->assertEquals(3, $partner->fresh()->sort_order);
    }

    public function test_sort_order_is_set_to_1_when_no_partners_exist(): void
    {
        $partner = Partner::create(['name' => 'Premier Partenaire']);

        $this->assertEquals(1, $partner->fresh()->sort_order);
    }

    public function test_sort_order_is_not_overridden_when_explicitly_set(): void
    {
        Partner::factory()->create(['sort_order' => 5]);

        $partner = Partner::create(['name' => 'Partenaire Positionné', 'sort_order' => 10]);

        $this->assertEquals(10, $partner->fresh()->sort_order);
    }

    public function test_sort_order_of_0_is_treated_as_not_set(): void
    {
        Partner::factory()->create(['sort_order' => 5]);

        $partner = Partner::create(['name' => 'Partenaire Zéro', 'sort_order' => 0]);

        // sort_order = 0 est traité comme "non défini" → l'observer l'auto-incrémente
        // Comportement: max(5) + 1 = 6
        // Note: 0 n'est pas une valeur métier valide pour sort_order (l'ordre commence à 1)
        $this->assertEquals(6, $partner->fresh()->sort_order);
    }
}
