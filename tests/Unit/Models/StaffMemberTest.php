<?php

namespace Tests\Unit\Models;

use App\Models\StaffMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_member_can_be_created_with_required_fields(): void
    {
        StaffMember::factory()->create([
            'name' => 'Jean Dupont',
            'type' => 'entraineur',
            'categories' => ['u13_m', 'u15_m'],
        ]);

        $this->assertDatabaseHas('staff_members', [
            'name' => 'Jean Dupont',
            'type' => 'entraineur',
        ]);
    }

    public function test_categories_is_cast_to_array(): void
    {
        $member = StaffMember::factory()->create(['categories' => ['u13_m', 'u15_m']]);

        $this->assertIsArray($member->fresh()->categories);
        $this->assertContains('u13_m', $member->fresh()->categories);
        $this->assertContains('u15_m', $member->fresh()->categories);
    }

    public function test_optional_fields_are_nullable(): void
    {
        $member = StaffMember::factory()->create([
            'photo' => null,
            'bio' => null,
        ]);

        $this->assertNull($member->photo);
        $this->assertNull($member->bio);
    }

    public function test_categories_constant_contains_14_entries(): void
    {
        $this->assertCount(14, StaffMember::CATEGORIES);
    }

    public function test_categories_constant_is_in_correct_order(): void
    {
        $keys = array_keys(StaffMember::CATEGORIES);
        $this->assertEquals('baby', $keys[0]);
        $this->assertEquals('u7', $keys[1]);
        $this->assertEquals('loisirs', $keys[13]);
    }
}
