<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_can_be_created(): void
    {
        $category = Category::factory()->create();
        Team::factory()->create([
            'category_id' => $category->id,
            'name'        => 'Équipe 1',
        ]);

        $this->assertDatabaseHas('teams', ['name' => 'Équipe 1']);
    }

    public function test_team_belongs_to_category(): void
    {
        $category = Category::factory()->create();
        $team = Team::factory()->create(['category_id' => $category->id]);

        $this->assertEquals($category->id, $team->category->id);
    }

    public function test_photo_and_scorenco_id_are_nullable(): void
    {
        $team = Team::factory()->create(['photo' => null, 'scorenco_id' => null]);

        $this->assertNull($team->photo);
        $this->assertNull($team->scorenco_id);
    }
}
