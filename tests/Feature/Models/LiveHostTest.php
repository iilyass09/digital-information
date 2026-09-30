<?php

namespace Tests\Feature\Models;

use App\Models\Host;
use App\Models\LiveHost;
use App\Models\LiveSchedule;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LiveHostTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_assignment_belongs_to_a_schedule_and_a_host_and_casts_its_date(): void
    {
        $schedule = LiveSchedule::factory()->create();
        $host = Host::factory()->create(['name' => 'Host Contoh']);
        $assignment = LiveHost::factory()->for($schedule)->for($host)->create(['date' => '2026-09-30']);

        $this->assertTrue($assignment->liveSchedule->is($schedule));
        $this->assertTrue($assignment->host->is($host));
        $this->assertSame('2026-09-30', $assignment->date->toDateString());
    }
}
