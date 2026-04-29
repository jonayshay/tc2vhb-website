<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_user_can_be_assigned_admin_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->assertTrue($user->hasRole('admin'));
        $this->assertFalse($user->hasRole('super_admin'));
    }

    public function test_user_can_be_assigned_super_admin_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->assertTrue($user->hasRole('super_admin'));
    }

    public function test_user_can_hold_multiple_roles(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');
        $user->assignRole('super_admin');

        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasRole('super_admin'));
    }

    public function test_user_without_role_cannot_access_panel(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->hasAnyRole(['admin', 'super_admin']));
    }
}
