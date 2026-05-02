<?php

namespace Tests\Unit\Models;

use App\Models\BoardMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoardMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_board_member_can_be_created_with_required_fields(): void
    {
        BoardMember::factory()->create([
            'name' => 'Jean Martin',
            'role' => 'Président',
        ]);

        $this->assertDatabaseHas('board_members', [
            'name' => 'Jean Martin',
            'role' => 'Président',
        ]);
    }

    public function test_bio_and_photo_are_nullable(): void
    {
        $member = BoardMember::factory()->create([
            'bio' => null,
            'photo' => null,
        ]);

        $this->assertNull($member->bio);
        $this->assertNull($member->photo);
    }
}
