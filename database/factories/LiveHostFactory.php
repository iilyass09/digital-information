<?php

namespace Database\Factories;

use App\Models\Host;
use App\Models\LiveHost;
use App\Models\LiveSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LiveHost>
 */
class LiveHostFactory extends Factory
{
    protected $model = LiveHost::class;

    public function definition(): array
    {
        return [
            'live_schedule_id' => LiveSchedule::factory(),
            'host_id' => Host::factory(),
            'date' => today(),
            'is_active' => true,
        ];
    }
}
