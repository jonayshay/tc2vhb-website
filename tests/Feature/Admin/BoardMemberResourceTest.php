<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoardMemberResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_access_board_members_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get('/admin/board-members')
            ->assertSuccessful();
    }

    public function test_super_admin_can_access_board_members_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)
            ->get('/admin/board-members')
            ->assertSuccessful();
    }

    public function test_unauthenticated_user_cannot_access_board_members_list(): void
    {
        $this->get('/admin/board-members')->assertRedirect('/admin/login');
    }

    public function test_authenticated_user_without_role_cannot_access_board_members_list(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin/board-members')
            ->assertForbidden();
    }
}
