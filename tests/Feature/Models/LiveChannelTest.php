<?php

namespace Tests\Feature\Models;

use App\Models\LiveChannel;
use App\Models\LiveHost;
use App\Models\LiveSchedule;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LiveChannelTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_channel_exposes_its_live_schedules(): void
    {
        $channel = LiveChannel::factory()->create(['name' => 'Johen PUBG']);
        LiveSchedule::factory()->for($channel)->create();
        LiveSchedule::factory()->for($channel)->create(['start_time' => '12:00:00', 'end_time' => '15:00:00']);

        $this->assertCount(2, $channel->liveSchedules);
        $this->assertSame(true, $channel->is_active);
    }

    public function test_deleting_a_channel_cascades_to_its_schedules_and_assignments(): void
    {
        $channel = LiveChannel::factory()->create();
        $schedule = LiveSchedule::factory()->for($channel)->create();
        $assignment = LiveHost::factory()->for($schedule)->create();

        $channel->delete();

        $this->assertDatabaseMissing('live_schedules', ['id' => $schedule->id]);
        $this->assertDatabaseMissing('live_hosts', ['id' => $assignment->id]);
    }
}
