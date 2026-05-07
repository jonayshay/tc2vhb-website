<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EquipesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_404_when_no_current_season(): void
    {
        $this->get('/equipes')->assertNotFound();
    }

    public function test_index_returns_categories_of_current_season(): void
    {
        $season = Season::factory()->create(['is_current' => true]);
        Category::factory()->create(['season_id' => $season->id, 'name' => 'U13 Masculins']);

        $this->get('/equipes')
            ->assertOk()
            ->assertInertia(fn (Assert $page) =>
                $page->component('Equipes/Index')
                    ->has('categories', 1)
                    ->where('categories.0.name', 'U13 Masculins')
            );
    }

    public function test_index_does_not_return_categories_from_other_seasons(): void
    {
        $current = Season::factory()->create(['is_current' => true]);
        $other   = Season::factory()->create(['is_current' => false]);
        Category::factory()->create(['season_id' => $current->id, 'name' => 'U13 Masculins']);
        Category::factory()->create(['season_id' => $other->id, 'name' => 'U15 Féminines']);

        $this->get('/equipes')
            ->assertOk()
            ->assertInertia(fn (Assert $page) =>
                $page->component('Equipes/Index')
                    ->has('categories', 1)
                    ->where('categories.0.name', 'U13 Masculins')
            );
    }

    public function test_show_returns_category_with_teams_and_players(): void
    {
        $season   = Season::factory()->create(['is_current' => true]);
        $category = Category::factory()->create(['season_id' => $season->id, 'slug' => 'u13-masculins', 'name' => 'U13 Masculins']);
        Team::factory()->create(['category_id' => $category->id, 'name' => 'Équipe 1']);
        Player::factory()->create(['category_id' => $category->id, 'last_name' => 'Dupont']);

        $this->get('/equipes/u13-masculins')
            ->assertOk()
            ->assertInertia(fn (Assert $page) =>
                $page->component('Equipes/Show')
                    ->where('category.name', 'U13 Masculins')
                    ->has('teams', 1)
                    ->has('players', 1)
            );
    }

    public function test_show_returns_404_for_unknown_slug(): void
    {
        Season::factory()->create(['is_current' => true]);

        $this->get('/equipes/inconnu')->assertNotFound();
    }

    public function test_show_returns_404_for_category_from_other_season(): void
    {
        Season::factory()->create(['is_current' => true]);
        $other    = Season::factory()->create(['is_current' => false]);
        Category::factory()->create(['season_id' => $other->id, 'slug' => 'u13-masculins']);

        $this->get('/equipes/u13-masculins')->assertNotFound();
    }

    public function test_show_returns_404_when_no_current_season(): void
    {
        $this->get('/equipes/u13-masculins')->assertNotFound();
    }

    public function test_players_are_ordered_by_last_name_then_first_name(): void
    {
        $season   = Season::factory()->create(['is_current' => true]);
        $category = Category::factory()->create(['season_id' => $season->id, 'slug' => 'u13']);
        Player::factory()->create(['category_id' => $category->id, 'last_name' => 'Martin',  'first_name' => 'Zoé']);
        Player::factory()->create(['category_id' => $category->id, 'last_name' => 'Dupont',  'first_name' => 'Alice']);
        Player::factory()->create(['category_id' => $category->id, 'last_name' => 'Dupont',  'first_name' => 'Abel']);

        $this->get('/equipes/u13')
            ->assertOk()
            ->assertInertia(fn (Assert $page) =>
                $page->component('Equipes/Show')
                    ->where('players.0.last_name', 'Dupont')
                    ->where('players.0.first_name', 'Abel')
                    ->where('players.1.last_name', 'Dupont')
                    ->where('players.1.first_name', 'Alice')
                    ->where('players.2.last_name', 'Martin')
            );
    }
}
