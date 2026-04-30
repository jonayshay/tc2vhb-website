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
}
