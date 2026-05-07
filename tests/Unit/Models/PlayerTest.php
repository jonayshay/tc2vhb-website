<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerTest extends TestCase
{
    use RefreshDatabase;

    public function test_player_can_be_created(): void
    {
        Player::factory()->create([
            'last_name'  => 'Dupont',
            'first_name' => 'Jean',
            'birth_date' => '2014-01-15',
        ]);

        $this->assertDatabaseHas('players', ['last_name' => 'Dupont']);
    }

    public function test_player_belongs_to_category(): void
    {
        $category = Category::factory()->create();
        $player = Player::factory()->create(['category_id' => $category->id]);

        $this->assertEquals($category->id, $player->category->id);
    }

    public function test_category_is_nullable(): void
    {
        $player = Player::factory()->create(['category_id' => null]);

        $this->assertNull($player->category_id);
    }

    public function test_optional_fields_are_nullable(): void
    {
        $player = Player::factory()->create([
            'photo'          => null,
            'gender'         => null,
            'license_number' => null,
        ]);

        $this->assertNull($player->photo);
        $this->assertNull($player->gender);
        $this->assertNull($player->license_number);
    }

    public function test_has_image_rights_defaults_to_false(): void
    {
        $player = Player::factory()->create();

        $this->assertFalse($player->has_image_rights);
    }
}
