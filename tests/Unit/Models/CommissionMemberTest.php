<?php

namespace Tests\Unit\Models;

use App\Models\Commission;
use App\Models\CommissionMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_commission_member_can_be_created_with_required_fields(): void
    {
        $commission = Commission::factory()->create();

        CommissionMember::factory()->create([
            'commission_id' => $commission->id,
            'name' => 'Marie Dupont',
            'role' => 'Présidente',
        ]);

        $this->assertDatabaseHas('commission_members', [
            'name' => 'Marie Dupont',
            'role' => 'Présidente',
        ]);
    }

    public function test_commission_member_belongs_to_commission(): void
    {
        $commission = Commission::factory()->create();
        $member = CommissionMember::factory()->create(['commission_id' => $commission->id]);

        $this->assertEquals($commission->id, $member->commission->id);
    }

    public function test_deleting_commission_cascades_to_members(): void
    {
        $commission = Commission::factory()->create();
        CommissionMember::factory()->create(['commission_id' => $commission->id]);

        $commission->delete();

        $this->assertDatabaseEmpty('commission_members');
    }

    public function test_bio_and_photo_are_nullable(): void
    {
        $commission = Commission::factory()->create();
        $member = CommissionMember::factory()->create([
            'commission_id' => $commission->id,
            'bio' => null,
            'photo' => null,
        ]);

        $this->assertNull($member->bio);
        $this->assertNull($member->photo);
    }
}
