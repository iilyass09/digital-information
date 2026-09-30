<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoundationRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_display_is_available_without_authentication(): void
    {
        $this->get('/')->assertOk();
        $this->getJson('/api/display')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.promotions', [])
            ->assertJsonPath('data.achievements', [])
            ->assertJsonPath('data.birthdays', [])
            ->assertJsonPath('data.weekly_meetings', [])
            ->assertJsonPath('data.live_channels', []);
    }

    public function test_admin_login_is_public_and_admin_pages_require_authentication(): void
    {
        $this->get('/admin/login')->assertOk();
        $this->get('/admin')->assertRedirect('/admin/login');

        foreach (['promotions', 'achievements', 'live-hosts', 'birthdays', 'hosts', 'channels', 'weekly-meetings', 'display-preview'] as $section) {
            $this->get('/admin/'.$section)->assertRedirect('/admin/login');
        }
    }

    public function test_admin_can_login_and_logout_while_regular_users_are_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->post('/admin/login', [
            'username' => $admin->username,
            'password' => 'password',
        ])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($admin);

        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->assertGuest();

        $user = User::factory()->create();
        $this->from('/admin/login')->post('/admin/login', [
            'username' => $user->username,
            'password' => 'password',
        ])->assertRedirect('/admin/login')->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_admin_api_requires_authentication(): void
    {
        $this->getJson('/api/admin')->assertUnauthorized();
    }

    public function test_admin_role_can_access_admin_pages_and_api_but_user_role_cannot(): void
    {
        $admin = (new User)->forceFill([
            'id' => 1,
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->getJson('/api/admin')
            ->assertOk()
            ->assertJsonPath('data.authenticated', true);

        $user = (new User)->forceFill([
            'id' => 2,
            'name' => 'User',
            'username' => 'user',
            'email' => 'user@example.com',
            'role' => 'user',
        ]);

        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->actingAs($user)->getJson('/api/admin')->assertForbidden();
    }
}
