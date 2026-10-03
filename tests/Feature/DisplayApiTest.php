<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\Birthday;
use App\Models\Host;
use App\Models\LiveChannel;
use App\Models\LiveHost;
use App\Models\LiveSchedule;
use App\Models\Promotion;
use App\Models\WeeklyMeeting;
use Database\Seeders\LiveChannelSeeder;
use Database\Seeders\LiveScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DisplayApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_display_api_returns_only_active_content_relevant_today(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 30)->startOfDay());
        $channel = LiveChannel::factory()->create([
            'name' => 'Johen PUBG',
            'logo' => 'channels/logo.png',
            'stream_url' => 'https://www.tiktok.com/@johen/live',
            'stream_logo' => 'live-channels/tiktok.png',
        ]);
        $host = Host::factory()->create(['name' => 'Host hari ini', 'photo' => 'hosts/host.jpg']);
        $activeSlot = LiveSchedule::factory()->for($channel)->create(['start_time' => '09:00:00', 'end_time' => '10:00:00', 'sort_order' => 1]);
        $inactiveSlot = LiveSchedule::factory()->for($channel)->create(['start_time' => '10:00:00', 'end_time' => '11:00:00', 'sort_order' => 2]);
        LiveSchedule::factory()->for($channel)->create(['start_time' => '11:00:00', 'end_time' => '12:00:00', 'sort_order' => 3]);

        Promotion::factory()->create(['title' => 'Promo tanpa periode', 'start_date' => null, 'end_date' => null, 'sort_order' => 1]);
        Promotion::factory()->create(['title' => 'Promo aktif', 'start_date' => '2026-09-01', 'end_date' => '2026-09-30', 'sort_order' => 2]);
        Promotion::factory()->create(['title' => 'Promo mendatang', 'start_date' => '2026-10-01', 'end_date' => '2026-10-31']);
        Promotion::factory()->create(['title' => 'Promo berakhir', 'start_date' => '2026-09-01', 'end_date' => '2026-09-29']);
        Promotion::factory()->create(['title' => 'Promo nonaktif', 'is_active' => false]);

        Achievement::factory()->create(['employee_name' => 'Ayu', 'is_active' => true]);
        Achievement::factory()->create(['employee_name' => 'Bima', 'is_active' => false]);

        LiveHost::factory()->for($activeSlot)->for($host)->create();
        LiveHost::factory()->for($inactiveSlot)->for($host)->create(['is_active' => false]);
        $inactiveChannel = LiveChannel::factory()->create(['is_active' => false]);
        $inactiveSchedule = LiveSchedule::factory()->for($inactiveChannel)->create();
        LiveHost::factory()->for($inactiveSchedule)->for($host)->create();

        Birthday::factory()->create(['employee_name' => 'Dina', 'birth_date' => '1994-09-30']);
        Birthday::factory()->create(['employee_name' => 'Eko', 'birth_date' => '1990-09-30']);
        Birthday::factory()->create(['employee_name' => 'Fira', 'birth_date' => '1992-09-29']);
        Birthday::factory()->create(['employee_name' => 'Gilang', 'birth_date' => '1995-09-30', 'is_active' => false]);

        $this->getJson('/api/display')->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data.promotions')
            ->assertJsonPath('data.promotions.0.title', 'Promo tanpa periode')
            ->assertJsonPath('data.promotions.1.title', 'Promo aktif')
            ->assertJsonCount(1, 'data.achievements')
            ->assertJsonPath('data.achievements.0.employee_name', 'Ayu')
            ->assertJsonCount(1, 'data.live_channels')
            ->assertJsonPath('data.live_channels.0.name', 'Johen PUBG')
            ->assertJsonPath('data.live_channels.0.logo_url', Storage::disk('public')->url('channels/logo.png'))
            ->assertJsonCount(3, 'data.live_channels.0.slots')
            ->assertJsonPath('data.live_channels.0.slots.0.host_name', 'Host hari ini')
            ->assertJsonPath('data.live_channels.0.slots.0.host_photo', Storage::disk('public')->url('hosts/host.jpg'))
            ->assertJsonPath('data.live_channels.0.slots.0.start_time', '09:00')
            ->assertJsonPath('data.live_channels.0.slots.0.end_time', '10:00')
            ->assertJsonPath('data.live_channels.0.slots.0.stream_link_name', 'Johen PUBG')
            ->assertJsonPath('data.live_channels.0.slots.0.stream_link_url', 'https://www.tiktok.com/@johen/live')
            ->assertJsonPath('data.live_channels.0.slots.0.stream_link_logo_url', Storage::disk('public')->url('live-channels/tiktok.png'))
            ->assertJsonPath('data.live_channels.0.slots.1.host_name', null)
            ->assertJsonPath('data.live_channels.0.slots.1.host_photo', null)
            ->assertJsonPath('data.live_channels.0.slots.1.stream_link_url', 'https://www.tiktok.com/@johen/live')
            ->assertJsonPath('data.live_channels.0.slots.2.host_name', null)
            ->assertJsonPath('data.live_channels.0.slots.2.stream_link_url', 'https://www.tiktok.com/@johen/live')
            ->assertJsonCount(2, 'data.birthdays')
            ->assertJsonPath('data.birthdays.0.employee_name', 'Dina')
            ->assertJsonPath('data.birthdays.1.employee_name', 'Eko')
            ->assertJsonMissingPath('data.promotions.0.created_at')
            ->assertJsonMissingPath('data.live_channels.0.slots.0.live_schedule_id');
    }

    public function test_recurring_schedule_and_channel_link_show_up_on_every_day(): void
    {
        $channel = LiveChannel::factory()->create(['stream_url' => 'https://www.tiktok.com/@johen/live']);
        $schedule = LiveSchedule::factory()->for($channel)->create(['start_time' => '09:00:00', 'end_time' => '10:00:00']);
        $host = Host::factory()->create(['name' => 'Fathan']);
        LiveHost::factory()->for($schedule)->for($host)->create();

        $assertVisible = fn (): TestResponse => $this->getJson('/api/display')->assertOk()
            ->assertJsonPath('data.live_channels.0.slots.0.host_name', 'Fathan')
            ->assertJsonPath('data.live_channels.0.slots.0.stream_link_url', 'https://www.tiktok.com/@johen/live');

        $this->travelTo(now()->setDate(2026, 9, 29)->startOfDay());
        $assertVisible();

        $this->travelTo(now()->setDate(2026, 12, 24)->startOfDay());
        $assertVisible();

        $this->travelTo(now()->setDate(2027, 1, 1)->startOfDay());
        $assertVisible();
    }

    public function test_public_display_api_includes_all_eight_seeded_active_channels(): void
    {
        $this->seed(LiveChannelSeeder::class);
        $this->seed(LiveScheduleSeeder::class);

        $this->getJson('/api/display')
            ->assertOk()
            ->assertJsonCount(8, 'data.live_channels')
            ->assertJsonPath('data.live_channels.0.name', 'Johen PUBG')
            ->assertJsonPath('data.live_channels.7.name', 'Monkey PUBG');
    }

    public function test_public_display_api_includes_weekly_meetings_on_any_day(): void
    {
        WeeklyMeeting::factory()->create(['name' => 'Nadia', 'photo' => 'weekly-meetings/nadia.jpg', 'sort_order' => 1]);
        WeeklyMeeting::factory()->create(['name' => 'Dimas', 'sort_order' => 2]);
        WeeklyMeeting::factory()->create(['name' => 'Nonaktif', 'is_active' => false]);

        $assertVisible = fn (): TestResponse => $this->getJson('/api/display')->assertOk()
            ->assertJsonCount(2, 'data.weekly_meetings')
            ->assertJsonPath('data.weekly_meetings.0.name', 'Nadia')
            ->assertJsonPath('data.weekly_meetings.0.photo_url', Storage::disk('public')->url('weekly-meetings/nadia.jpg'))
            ->assertJsonPath('data.weekly_meetings.1.name', 'Dimas')
            ->assertJsonMissingPath('data.weekly_meetings.0.start_time')
            ->assertJsonMissingPath('data.weekly_meetings.0.end_time')
            ->assertJsonMissingPath('data.weekly_meetings.0.created_at');

        $this->travelTo(now()->setDate(2026, 9, 29)->startOfDay());
        $assertVisible();

        $this->travelTo(now()->setDate(2026, 9, 30)->startOfDay());
        $assertVisible();
    }

    public function test_public_display_api_returns_consistent_empty_collections_without_login(): void
    {
        $this->getJson('/api/display')->assertOk()
            ->assertExactJson([
                'success' => true,
                'data' => [
                    'promotions' => [],
                    'achievements' => [],
                    'birthdays' => [],
                    'weekly_meetings' => [],
                    'live_channels' => [],
                ],
            ]);
    }
}
