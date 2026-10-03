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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminCmsFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_cms_routes_and_admin_api_are_restricted_to_admins(): void
    {
        $this->get('/admin/promotions')->assertRedirect('/admin/login');
        $this->getJson('/api/admin/promotions')->assertUnauthorized();

        $user = User::factory()->create();
        $this->actingAs($user)->get('/admin/promotions')->assertForbidden();
        $this->actingAs($user)->getJson('/api/admin/promotions')->assertForbidden();

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get('/admin/promotions')->assertOk();
        $this->actingAs($admin)->get('/')->assertOk();
    }

    public function test_admin_can_create_update_toggle_filter_and_delete_content(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $this->postJson('/api/admin/promotions', [
            'title' => 'Promo Gajian', 'description' => 'Diskon khusus', 'start_date' => '2026-09-01',
            'end_date' => '2026-10-01', 'is_active' => true, 'sort_order' => 2,
            'image' => UploadedFile::fake()->image('banner.jpg'),
        ])->assertCreated()->assertJsonPath('data.title', 'Promo Gajian');
        $promotion = Promotion::firstOrFail();
        $this->assertStringStartsWith('promotions/', $promotion->image);
        Storage::disk('public')->assertExists($promotion->image);

        $this->patchJson('/api/admin/promotions/'.$promotion->id.'/toggle')->assertOk()->assertJsonPath('data.is_active', false);
        $this->putJson('/api/admin/promotions/'.$promotion->id, ['title' => 'Promo Revisi'])->assertOk()->assertJsonPath('data.title', 'Promo Revisi');

        $host = Host::factory()->create(['name' => 'Dimas']);
        $this->getJson('/api/admin/hosts')
            ->assertOk()->assertJsonPath('data.data.0.id', $host->id)->assertJsonPath('data.data.0.name', 'Dimas');

        $this->deleteJson('/api/admin/promotions/'.$promotion->id)->assertOk();
        $this->assertDatabaseMissing('promotions', ['id' => $promotion->id]);
        Storage::disk('public')->assertMissing($promotion->image);
    }

    public function test_admin_can_manage_achievements_birthdays_and_hosts(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $achievementResponse = $this->postJson('/api/admin/achievements', [
            'employee_name' => 'Nadia', 'division' => 'Operations', 'title' => 'Top Performer',
            'description' => 'September result', 'achievement_date' => '2026-09-30', 'sort_order' => 1,
        ])->assertCreated();
        $achievementId = $achievementResponse->json('data.id');
        $this->putJson('/api/admin/achievements/'.$achievementId, ['title' => 'Employee of the Month'])->assertOk();
        $this->deleteJson('/api/admin/achievements/'.$achievementId)->assertOk();

        $birthdayResponse = $this->postJson('/api/admin/birthdays', [
            'employee_name' => 'Raka', 'division' => 'Creative', 'birth_date' => '1997-04-12', 'is_active' => true, 'sort_order' => 2,
        ])->assertCreated()->assertJsonPath('data.sort_order', 2);
        $birthdayId = $birthdayResponse->json('data.id');
        $this->patchJson('/api/admin/birthdays/'.$birthdayId.'/toggle')->assertOk()->assertJsonPath('data.is_active', false);
        $this->deleteJson('/api/admin/birthdays/'.$birthdayId)->assertOk();

        $hostResponse = $this->postJson('/api/admin/hosts', [
            'name' => 'Fathan', 'is_active' => true, 'sort_order' => 3,
        ])->assertCreated()->assertJsonPath('data.name', 'Fathan');
        $hostId = $hostResponse->json('data.id');
        $this->putJson('/api/admin/hosts/'.$hostId, ['name' => 'Fathan Revisi'])->assertOk()->assertJsonPath('data.name', 'Fathan Revisi');
        $this->patchJson('/api/admin/hosts/'.$hostId.'/toggle')->assertOk()->assertJsonPath('data.is_active', false);
        $this->deleteJson('/api/admin/hosts/'.$hostId)->assertOk();
    }

    public function test_admin_can_read_and_update_the_recurring_live_host_board(): void
    {
        $admin = User::factory()->admin()->create();
        $channel = LiveChannel::factory()->create(['name' => 'Johen PUBG']);
        $schedule = LiveSchedule::factory()->for($channel)->create(['start_time' => '09:00:00', 'end_time' => '12:00:00']);
        $host = Host::factory()->create(['name' => 'Fathan']);
        $this->actingAs($admin);

        $this->getJson('/api/admin/live-hosts/board')->assertOk()
            ->assertJsonCount(1, 'data.channels')
            ->assertJsonPath('data.channels.0.slots.0.host_id', null);

        $this->putJson('/api/admin/live-hosts/board', [
            'assignments' => [
                ['live_schedule_id' => $schedule->id, 'host_id' => $host->id],
            ],
        ])->assertOk()
            ->assertJsonPath('data.channels.0.slots.0.host_id', $host->id)
            ->assertJsonPath('data.channels.0.slots.0.host_name', 'Fathan');

        $this->assertDatabaseHas('live_hosts', [
            'live_schedule_id' => $schedule->id, 'host_id' => $host->id,
        ]);

        $this->putJson('/api/admin/live-hosts/board', [
            'assignments' => [
                ['live_schedule_id' => $schedule->id, 'host_id' => null],
            ],
        ])->assertOk()->assertJsonPath('data.channels.0.slots.0.host_id', null);
    }

    public function test_saving_a_slot_replaces_the_previous_assignment_instead_of_adding_one(): void
    {
        $admin = User::factory()->admin()->create();
        $channel = LiveChannel::factory()->create();
        $schedule = LiveSchedule::factory()->for($channel)->create();
        $firstHost = Host::factory()->create(['name' => 'Fathan']);
        $secondHost = Host::factory()->create(['name' => 'Rafly']);
        $this->actingAs($admin);

        LiveHost::factory()->for($schedule)->for($firstHost)->create();

        $this->putJson('/api/admin/live-hosts/board', [
            'assignments' => [
                ['live_schedule_id' => $schedule->id, 'host_id' => $secondHost->id],
            ],
        ])->assertOk()->assertJsonPath('data.channels.0.slots.0.host_name', 'Rafly');

        $this->assertSame(1, LiveHost::where('live_schedule_id', $schedule->id)->count());
        $this->assertDatabaseHas('live_hosts', [
            'live_schedule_id' => $schedule->id, 'host_id' => $secondHost->id,
        ]);
    }

    public function test_admin_can_open_the_create_and_edit_form_for_every_resource(): void
    {
        $admin = User::factory()->admin()->create();
        $birthday = Birthday::factory()->create();
        $host = Host::factory()->create();
        $this->actingAs($admin);

        foreach (['promotions', 'achievements', 'birthdays', 'hosts', 'channels', 'weekly-meetings'] as $resource) {
            $this->get("/admin/{$resource}/create")->assertOk();
            $this->get("/admin/{$resource}/{$birthday->id}/edit")->assertOk();
            $this->get("/admin/{$resource}/{$host->id}/edit")->assertOk();
        }
        $this->get('/admin/live-hosts')->assertOk();
    }

    public function test_admin_can_manage_channels_with_logo_and_hosts_with_photo(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $channelResponse = $this->postJson('/api/admin/channels', [
            'name' => 'Johen PUBG', 'is_active' => true, 'sort_order' => 1,
            'logo' => UploadedFile::fake()->image('logo.png'),
        ])->assertCreated()->assertJsonPath('data.name', 'Johen PUBG');
        $channelId = $channelResponse->json('data.id');
        $channel = LiveChannel::findOrFail($channelId);
        $this->assertStringStartsWith('channels/', $channel->logo);
        Storage::disk('public')->assertExists($channel->logo);

        $this->patchJson('/api/admin/channels/'.$channelId.'/toggle')->assertOk()->assertJsonPath('data.is_active', false);
        $this->putJson('/api/admin/channels/'.$channelId, ['name' => 'Johen PUBG Pro'])->assertOk()->assertJsonPath('data.name', 'Johen PUBG Pro');
        $this->deleteJson('/api/admin/channels/'.$channelId)->assertOk();
        $this->assertDatabaseMissing('live_channels', ['id' => $channelId]);
        Storage::disk('public')->assertMissing($channel->logo);

        LiveChannel::factory()->create(['name' => 'Johen MLBB']);
        $this->postJson('/api/admin/channels', ['name' => 'Johen MLBB'])
            ->assertUnprocessable()->assertJsonValidationErrors('name');

        $hostResponse = $this->postJson('/api/admin/hosts', [
            'name' => 'Fathan', 'photo' => UploadedFile::fake()->image('fathan.jpg'),
        ])->assertCreated()->assertJsonPath('data.name', 'Fathan');
        $hostId = $hostResponse->json('data.id');
        $host = Host::findOrFail($hostId);
        $this->assertStringStartsWith('hosts/', $host->photo);
        Storage::disk('public')->assertExists($host->photo);
        $this->deleteJson('/api/admin/hosts/'.$hostId)->assertOk();
        $this->assertDatabaseMissing('hosts', ['id' => $hostId]);
        Storage::disk('public')->assertMissing($host->photo);
    }

    public function test_admin_can_manage_weekly_meetings_with_photo(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $response = $this->postJson('/api/admin/weekly-meetings', [
            'name' => 'Nadia', 'sort_order' => 2,
            'photo' => UploadedFile::fake()->image('nadia.jpg'),
        ])->assertCreated()->assertJsonPath('data.name', 'Nadia');
        $id = $response->json('data.id');
        $meeting = WeeklyMeeting::findOrFail($id);
        $this->assertStringStartsWith('weekly-meetings/', $meeting->photo);
        Storage::disk('public')->assertExists($meeting->photo);

        $this->patchJson('/api/admin/weekly-meetings/'.$id.'/toggle')->assertOk()->assertJsonPath('data.is_active', false);
        $this->putJson('/api/admin/weekly-meetings/'.$id, ['name' => 'Nadia Revisi'])->assertOk()->assertJsonPath('data.name', 'Nadia Revisi');
        $this->deleteJson('/api/admin/weekly-meetings/'.$id)->assertOk();
        $this->assertDatabaseMissing('weekly_meetings', ['id' => $id]);
        Storage::disk('public')->assertMissing($meeting->photo);

        WeeklyMeeting::factory()->create(['name' => 'Dimas']);
        $this->postJson('/api/admin/weekly-meetings', ['name' => 'Dimas'])->assertUnprocessable()->assertJsonValidationErrors('name');
    }

    public function test_admin_content_index_returns_pagination_metadata(): void
    {
        $admin = User::factory()->admin()->create();
        Host::factory()->count(20)->create();

        // The admin list renders "Halaman X dari Y" and enables the previous /
        // next buttons from these fields, so they must survive serialisation.
        $this->actingAs($admin)->getJson('/api/admin/hosts')->assertOk()
            ->assertJsonPath('data.current_page', 1)
            ->assertJsonPath('data.last_page', 2)
            ->assertJsonPath('data.per_page', 15)
            ->assertJsonPath('data.total', 20)
            ->assertJsonCount(15, 'data.data')
            ->assertJsonPath('data.prev_page_url', null)
            ->assertJsonStructure(['data' => ['next_page_url']]);

        $this->actingAs($admin)->getJson('/api/admin/hosts?page=2')->assertOk()
            ->assertJsonPath('data.current_page', 2)
            ->assertJsonCount(5, 'data.data')
            ->assertJsonPath('data.next_page_url', null)
            ->assertJsonStructure(['data' => ['prev_page_url']]);
    }

    public function test_admin_can_set_one_stream_link_per_channel_from_the_board(): void
    {
        $admin = User::factory()->admin()->create();
        $channel = LiveChannel::factory()->create(['name' => 'Johen PUBG']);
        $schedule = LiveSchedule::factory()->for($channel)->create(['start_time' => '09:00:00', 'end_time' => '12:00:00']);
        $this->actingAs($admin);

        $this->getJson('/api/admin/live-hosts/board')->assertOk()
            ->assertJsonPath('data.channels.0.stream_url', null)
            ->assertJsonPath('data.channels.0.stream_logo_url', null)
            ->assertJsonMissingPath('data.stream_links');

        $this->putJson('/api/admin/live-hosts/board', [
            'assignments' => [['live_schedule_id' => $schedule->id, 'host_id' => null]],
            'channels' => [[
                'live_channel_id' => $channel->id,
                'stream_url' => 'https://www.tiktok.com/@johen/live',
            ]],
        ])->assertOk()->assertJsonPath('data.channels.0.stream_url', 'https://www.tiktok.com/@johen/live');

        $this->assertDatabaseHas('live_channels', [
            'id' => $channel->id, 'stream_url' => 'https://www.tiktok.com/@johen/live',
        ]);

        $this->putJson('/api/admin/live-hosts/board', [
            'assignments' => [['live_schedule_id' => $schedule->id, 'host_id' => null]],
            'channels' => [['live_channel_id' => $channel->id, 'stream_url' => 'bukan-url']],
        ])->assertUnprocessable()->assertJsonValidationErrors('channels.0.stream_url');

        $this->post('/api/admin/live-hosts/board', [
            '_method' => 'PUT',
            'assignments' => [['live_schedule_id' => $schedule->id, 'host_id' => null]],
            'channels' => [['live_channel_id' => $channel->id, 'stream_logo' => UploadedFile::fake()->create('logo.pdf', 10, 'application/pdf')]],
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('channels.0.stream_logo');
    }

    public function test_admin_can_upload_a_channel_stream_logo(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $channel = LiveChannel::factory()->create();
        $schedule = LiveSchedule::factory()->for($channel)->create();
        $this->actingAs($admin);

        $response = $this->post('/api/admin/live-hosts/board', [
            '_method' => 'PUT',
            'assignments' => [['live_schedule_id' => $schedule->id, 'host_id' => null]],
            'channels' => [[
                'live_channel_id' => $channel->id,
                'stream_url' => 'https://www.tiktok.com/@johen/live',
                'stream_logo' => UploadedFile::fake()->image('tiktok.png'),
            ]],
        ], ['Accept' => 'application/json'])->assertOk()
            ->assertJsonPath('data.channels.0.stream_url', 'https://www.tiktok.com/@johen/live');

        $channel->refresh();
        $this->assertStringStartsWith('live-channels/', $channel->stream_logo);
        Storage::disk('public')->assertExists($channel->stream_logo);
        $this->assertSame(Storage::disk('public')->url($channel->stream_logo), $response->json('data.channels.0.stream_logo_url'));
    }

    public function test_admin_can_store_a_long_tiktok_share_url_on_a_channel(): void
    {
        $admin = User::factory()->admin()->create();
        $channel = LiveChannel::factory()->create();
        $schedule = LiveSchedule::factory()->for($channel)->create();
        $this->actingAs($admin);

        $longUrl = 'https://www.tiktok.com/@johengaming77/live?_d=secCgYIASAHKAESPgo8'.str_repeat('aBcD1234', 40);

        $this->assertGreaterThan(255, strlen($longUrl));

        $this->putJson('/api/admin/live-hosts/board', [
            'assignments' => [['live_schedule_id' => $schedule->id, 'host_id' => null]],
            'channels' => [['live_channel_id' => $channel->id, 'stream_url' => $longUrl]],
        ])->assertOk()->assertJsonPath('data.channels.0.stream_url', $longUrl);

        $this->assertDatabaseHas('live_channels', ['id' => $channel->id, 'stream_url' => $longUrl]);
    }

    public function test_clearing_a_channel_stream_url_leaves_the_slot_assignments_untouched(): void
    {
        $admin = User::factory()->admin()->create();
        $channel = LiveChannel::factory()->create();
        $schedule = LiveSchedule::factory()->for($channel)->create();
        $host = Host::factory()->create(['name' => 'Fathan']);
        $this->actingAs($admin);

        $channel->update(['stream_url' => 'https://www.tiktok.com/@johen/live']);

        $this->putJson('/api/admin/live-hosts/board', [
            'assignments' => [['live_schedule_id' => $schedule->id, 'host_id' => $host->id]],
            'channels' => [['live_channel_id' => $channel->id, 'stream_url' => '']],
        ])->assertOk()
            ->assertJsonPath('data.channels.0.stream_url', null)
            ->assertJsonPath('data.channels.0.slots.0.host_name', 'Fathan');
    }

    public function test_admin_birthdays_index_orders_records_by_sort_order(): void
    {
        $admin = User::factory()->admin()->create();
        $later = Birthday::factory()->create(['employee_name' => 'Zeta', 'sort_order' => 5]);
        $earlier = Birthday::factory()->create(['employee_name' => 'Alfa', 'sort_order' => 1]);

        $this->actingAs($admin)->getJson('/api/admin/birthdays')->assertOk()
            ->assertJsonPath('data.data.0.id', $earlier->id)
            ->assertJsonPath('data.data.1.id', $later->id);
    }

    public function test_admin_content_endpoints_validate_dates_channels_and_images(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);
        $this->postJson('/api/admin/promotions', [
            'title' => 'Invalid range', 'start_date' => '2026-10-02', 'end_date' => '2026-10-01',
        ])->assertUnprocessable()->assertJsonValidationErrors('end_date');

        $this->postJson('/api/admin/birthdays', [
            'employee_name' => 'Nadia', 'division' => 'Operations', 'birth_date' => '2026-02-30',
        ])->assertUnprocessable()->assertJsonValidationErrors('birth_date');

        Host::factory()->create(['name' => 'Fathan']);
        $this->postJson('/api/admin/hosts', ['name' => 'Fathan', 'sort_order' => -1])
            ->assertUnprocessable()->assertJsonValidationErrors(['name', 'sort_order']);

        $this->putJson('/api/admin/live-hosts/board', [
            'assignments' => [['live_schedule_id' => 987654, 'host_id' => 123]],
        ])->assertUnprocessable()->assertJsonValidationErrors(['assignments.0.live_schedule_id', 'assignments.0.host_id']);

        $this->postJson('/api/admin/achievements', [
            'employee_name' => 'Nadia', 'division' => 'Ops', 'title' => 'Top',
            'achievement_date' => '2026-09-30', 'image' => UploadedFile::fake()->create('file.pdf', 50, 'application/pdf'),
        ])->assertUnprocessable()->assertJsonValidationErrors('image');
    }

    public function test_admin_can_upload_an_animated_gif_promotion_banner(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $this->postJson('/api/admin/promotions', [
            'title' => 'Promo Animasi', 'image' => UploadedFile::fake()->image('banner.gif'),
        ])->assertCreated()->assertJsonPath('data.title', 'Promo Animasi');

        $promotion = Promotion::firstOrFail();
        $this->assertStringStartsWith('promotions/', $promotion->image);
        $this->assertStringEndsWith('.gif', $promotion->image);
        Storage::disk('public')->assertExists($promotion->image);
    }

    public function test_gif_is_only_accepted_for_promotion_banners(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $this->postJson('/api/admin/achievements', [
            'employee_name' => 'Nadia', 'division' => 'Operations', 'title' => 'Top Sales',
            'achievement_date' => '2026-09-30', 'image' => UploadedFile::fake()->image('foto.gif'),
        ])->assertUnprocessable()->assertJsonValidationErrors('image');
    }

    public function test_promotion_banner_validation_messages_are_translated(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $this->postJson('/api/admin/promotions', [
            'title' => 'Format salah', 'image' => UploadedFile::fake()->image('poster.bmp'),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('image')
            ->assertJsonPath('errors.image.0', 'Banner harus berformat JPG, PNG, WebP, atau GIF.');
    }

    public function test_dashboard_counts_active_records_for_today(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 30)->startOfDay());
        $admin = User::factory()->admin()->create();
        $channel = LiveChannel::factory()->create();
        $schedule = LiveSchedule::factory()->for($channel)->create();
        $secondSchedule = LiveSchedule::factory()->for($channel)->create(['start_time' => '12:00:00', 'end_time' => '15:00:00']);
        $inactiveSchedule = LiveSchedule::factory()->for($channel)->create(['start_time' => '15:00:00', 'end_time' => '18:00:00']);
        $host = Host::factory()->create();
        Promotion::factory()->create();
        Promotion::factory()->create(['is_active' => false]);
        Achievement::factory()->create();
        LiveHost::factory()->for($schedule)->for($host)->create();
        LiveHost::factory()->for($secondSchedule)->for($host)->create();
        LiveHost::factory()->for($inactiveSchedule)->for($host)->create(['is_active' => false]);
        Birthday::factory()->create(['birth_date' => '1990-09-30']);

        $this->actingAs($admin)->getJson('/api/admin/dashboard')->assertOk()
            ->assertJsonPath('data.active_promotions', 1)->assertJsonPath('data.active_achievements', 1)
            ->assertJsonPath('data.assigned_hosts', 2)->assertJsonPath('data.today_birthdays', 1);
    }
}
