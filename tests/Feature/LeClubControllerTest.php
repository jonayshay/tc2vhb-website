<?php

namespace Tests\Feature;

use App\Models\ClubPresentation;
use App\Models\StaffMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LeClubControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_retourne_la_page_navigation(): void
    {
        $this->get('/le-club')
            ->assertOk()
            ->assertInertia(fn (Assert $page) =>
                $page->component('LeClub/Index')
            );
    }

    public function test_presentation_retourne_le_contenu_du_club(): void
    {
        ClubPresentation::factory()->create([
            'title' => 'Notre club',
            'accroche' => 'Un club passionné',
        ]);

        $this->get('/le-club/presentation')
            ->assertOk()
            ->assertInertia(fn (Assert $page) =>
                $page->component('LeClub/Presentation')
                    ->where('presentation.title', 'Notre club')
                    ->where('presentation.accroche', 'Un club passionné')
            );
    }

    public function test_entraineurs_retourne_les_membres_de_type_entraineur(): void
    {
        StaffMember::factory()->entraineur()->create(['name' => 'Coach A', 'categories' => ['u13_m']]);
        StaffMember::factory()->arbitre()->create(['name' => 'Arbitre B', 'categories' => ['u13_m']]);

        $this->get('/le-club/entraineurs')
            ->assertOk()
            ->assertInertia(fn (Assert $page) =>
                $page->component('LeClub/Entraineurs')
                    ->has('groupes', 1)
                    ->where('groupes.0.membres.0.name', 'Coach A')
            );
    }

    public function test_entraineurs_sont_groupes_dans_lordre_des_categories(): void
    {
        StaffMember::factory()->entraineur()->create(['categories' => ['u15_m']]);
        StaffMember::factory()->entraineur()->create(['categories' => ['u13_m']]);

        $this->get('/le-club/entraineurs')
            ->assertOk()
            ->assertInertia(fn (Assert $page) =>
                $page->component('LeClub/Entraineurs')
                    ->where('groupes.0.categorie', 'U13 Masculins')
                    ->where('groupes.1.categorie', 'U15 Masculins')
            );
    }

    public function test_categories_sans_membres_nexistent_pas_dans_groupes(): void
    {
        StaffMember::factory()->entraineur()->create(['categories' => ['seniors_m']]);

        $this->get('/le-club/entraineurs')
            ->assertOk()
            ->assertInertia(fn (Assert $page) =>
                $page->component('LeClub/Entraineurs')
                    ->has('groupes', 1)
                    ->where('groupes.0.categorie', 'Seniors Masculins')
            );
    }

    public function test_entraineurs_retourne_groupes_vide_sans_membres(): void
    {
        $this->get('/le-club/entraineurs')
            ->assertOk()
            ->assertInertia(fn (Assert $page) =>
                $page->component('LeClub/Entraineurs')
                    ->where('groupes', [])
            );
    }

    public function test_arbitres_retourne_les_membres_de_type_arbitre(): void
    {
        StaffMember::factory()->arbitre()->create(['name' => 'Arbitre C', 'categories' => ['seniors_m']]);
        StaffMember::factory()->entraineur()->create(['name' => 'Coach D', 'categories' => ['seniors_m']]);

        $this->get('/le-club/arbitres')
            ->assertOk()
            ->assertInertia(fn (Assert $page) =>
                $page->component('LeClub/Arbitres')
                    ->has('groupes', 1)
                    ->where('groupes.0.membres.0.name', 'Arbitre C')
            );
    }
}
