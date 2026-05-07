<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Season;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeasonTest extends TestCase
{
    use RefreshDatabase;

    public function test_season_can_be_created(): void
    {
        Season::factory()->create([
            'name' => '2026-2027',
            'is_current' => false,
        ]);

        $this->assertDatabaseHas('seasons', ['name' => '2026-2027']);
    }

    public function test_season_has_many_categories(): void
    {
        $season = Season::factory()->create();
        Category::factory()->create(['season_id' => $season->id]);
        Category::factory()->create(['season_id' => $season->id]);

        $this->assertCount(2, $season->categories);
    }
}
