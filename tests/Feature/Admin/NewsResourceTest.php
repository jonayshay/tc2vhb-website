<?php

namespace Tests\Feature\Admin;

use App\Models\News;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_access_news_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get('/admin/news')
            ->assertSuccessful();
    }

    public function test_super_admin_can_access_news_list(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)
            ->get('/admin/news')
            ->assertSuccessful();
    }

    public function test_unauthenticated_user_cannot_access_news_list(): void
    {
        $this->get('/admin/news')->assertRedirect('/admin/login');
    }
}
