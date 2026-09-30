<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\Birthday;
use App\Models\Host;
use App\Models\LiveChannel;
use App\Models\LiveHost;
use App\Models\LiveSchedule;
use App\Models\Promotion;
use App\Models\User;
use App\Models\WeeklyMeeting;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_database_seeder_creates_default_channels_and_local_sample_records(): void
    {
        $channelNames = [
            'Johen PUBG',
            'Johen MLBB',
            'Johen Roblox',
            'Johen Valorant',
            'Johen Free Fire',
            'Johen E-Football',
            'Johen FC Mobile',
            'Monkey PUBG',
        ];

        $this->seed();

        $this->assertDatabaseHas('users', ['username' => 'admin', 'email' => 'admin@example.com', 'role' => 'admin']);
        $this->assertTrue(Hash::check('admin', User::query()->where('username', 'admin')->firstOrFail()->password));

        $admin = User::query()->where('username', 'admin')->firstOrFail();
        $this->post('/admin/login', ['username' => 'admin', 'password' => 'admin'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($admin);

        $this->assertSame($channelNames, LiveChannel::query()->orderBy('sort_order')->pluck('name')->all());
        $this->assertSame(32, Host::count());
        $this->assertSame(32, LiveSchedule::count());
        $this->assertSame(32, LiveHost::count());
        $this->assertSame(1, Promotion::count());
        $this->assertSame(1, Achievement::count());
        $this->assertSame(1, Birthday::count());
        $this->assertSame(8, WeeklyMeeting::count());

        $this->seed();

        $this->assertSame(8, LiveChannel::count());
        $this->assertSame(32, Host::count());
        $this->assertSame(32, LiveSchedule::count());
        $this->assertSame(32, LiveHost::count());
        $this->assertSame(1, Promotion::count());
        $this->assertSame(1, Achievement::count());
        $this->assertSame(1, Birthday::count());
        $this->assertSame(8, WeeklyMeeting::count());
    }

    public function test_admin_seeder_hashes_configured_credentials_and_assigns_admin_role(): void
    {
        config([
            'admin.seed.name' => 'Seed Admin',
            'admin.seed.username' => 'seed-admin',
            'admin.seed.email' => 'seed-admin@example.com',
            'admin.seed.password' => 'test-password-123',
        ]);

        $this->seed(AdminUserSeeder::class);

        $admin = User::query()->where('email', 'seed-admin@example.com')->firstOrFail();

        $this->assertSame('admin', $admin->role);
        $this->assertTrue(Hash::check('test-password-123', $admin->password));
    }
}
