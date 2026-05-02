<?php

namespace Tests\Feature;

use App\Models\Season;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeasonObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_when_season_set_as_current_others_become_not_current(): void
    {
        $first = Season::factory()->create(['is_current' => true]);
        $second = Season::factory()->create(['is_current' => false]);

        $second->update(['is_current' => true]);

        $this->assertFalse($first->fresh()->is_current);
        $this->assertTrue($second->fresh()->is_current);
    }

    public function test_is_current_false_does_not_affect_others(): void
    {
        $first = Season::factory()->create(['is_current' => true]);
        $second = Season::factory()->create(['is_current' => false]);

        $second->update(['name' => '2027-2028']);

        $this->assertTrue($first->fresh()->is_current);
    }

    public function test_updating_other_fields_does_not_affect_is_current(): void
    {
        $first = Season::factory()->create(['is_current' => true]);
        $first->update(['name' => 'Nouvelle saison']);

        $this->assertTrue($first->fresh()->is_current);
    }
}
