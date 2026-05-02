<?php

namespace Tests\Unit\Models;

use App\Models\Commission;
use App\Models\CommissionMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_commission_can_be_created_with_required_fields(): void
    {
        Commission::factory()->create(['name' => 'Commission sportive']);

        $this->assertDatabaseHas('commissions', ['name' => 'Commission sportive']);
    }

    public function test_description_is_nullable(): void
    {
        $commission = Commission::factory()->create(['description' => null]);

        $this->assertNull($commission->description);
    }

    public function test_commission_has_many_members(): void
    {
        $commission = Commission::factory()->create();
        CommissionMember::factory()->create(['commission_id' => $commission->id]);
        CommissionMember::factory()->create(['commission_id' => $commission->id]);

        $this->assertCount(2, $commission->members);
    }
}
