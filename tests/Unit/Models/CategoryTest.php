<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_can_be_created(): void
    {
        $season = Season::factory()->create();
        Category::factory()->create([
            'season_id' => $season->id,
            'name'      => 'U13 Masculins',
            'gender'    => 'M',
        ]);

        $this->assertDatabaseHas('categories', ['name' => 'U13 Masculins']);
    }

    public function test_category_belongs_to_season(): void
    {
        $season = Season::factory()->create();
        $category = Category::factory()->create(['season_id' => $season->id]);

        $this->assertEquals($season->id, $category->season->id);
    }

    public function test_category_has_many_teams(): void
    {
        $category = Category::factory()->create();
        Team::factory()->create(['category_id' => $category->id]);
        Team::factory()->create(['category_id' => $category->id]);

        $this->assertCount(2, $category->teams);
    }

    public function test_category_has_many_players(): void
    {
        $category = Category::factory()->create();
        Player::factory()->create(['category_id' => $category->id]);
        Player::factory()->create(['category_id' => $category->id]);

        $this->assertCount(2, $category->players);
    }

    public function test_slug_is_auto_generated_from_name_when_empty(): void
    {
        $category = Category::factory()->create([
            'name' => 'U13 Masculins',
            'slug' => '',
        ]);

        $this->assertEquals('u13-masculins', $category->fresh()->slug);
    }
}
