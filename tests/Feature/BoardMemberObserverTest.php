<?php

namespace Tests\Feature;

use App\Models\BoardMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoardMemberObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_sort_order_is_auto_incremented_when_not_provided(): void
    {
        $first = BoardMember::factory()->create(['sort_order' => 0]);
        $second = BoardMember::factory()->create(['sort_order' => 0]);

        $this->assertEquals(1, $first->fresh()->sort_order);
        $this->assertEquals(2, $second->fresh()->sort_order);
    }

    public function test_sort_order_is_preserved_when_explicitly_set(): void
    {
        $member = BoardMember::factory()->create(['sort_order' => 5]);

        $this->assertEquals(5, $member->fresh()->sort_order);
    }
}
