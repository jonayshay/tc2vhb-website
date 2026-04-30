<?php

namespace Tests\Unit\Models;

use App\Models\Partner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_can_be_created_with_required_fields(): void
    {
        $partner = Partner::factory()->create([
            'name' => 'Sponsor Principal',
            'sort_order' => 1,
        ]);

        $this->assertDatabaseHas('partners', [
            'name' => 'Sponsor Principal',
            'sort_order' => 1,
        ]);
    }

    public function test_optional_fields_are_nullable(): void
    {
        $partner = Partner::factory()->create([
            'logo' => null,
            'url' => null,
            'description' => null,
        ]);

        $this->assertNull($partner->logo);
        $this->assertNull($partner->url);
        $this->assertNull($partner->description);
    }
}
