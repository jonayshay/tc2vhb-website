<?php

namespace Tests\Unit\Models;

use App\Models\ClubPresentation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClubPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_club_presentation_can_be_created(): void
    {
        ClubPresentation::factory()->create([
            'title' => 'Notre club',
            'accroche' => 'Un club passionné',
            'content' => '<p>Contenu test</p>',
        ]);

        $this->assertDatabaseHas('club_presentations', [
            'title' => 'Notre club',
            'accroche' => 'Un club passionné',
            'featured_image' => null,
        ]);
    }

    public function test_featured_image_is_nullable(): void
    {
        $presentation = ClubPresentation::factory()->create(['featured_image' => null]);

        $this->assertNull($presentation->featured_image);
    }
}
