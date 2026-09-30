<?php

namespace Database\Factories;

use App\Models\LiveChannel;
use App\Models\LiveSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LiveSchedule>
 */
class LiveScheduleFactory extends Factory
{
    protected $model = LiveSchedule::class;

    public function definition(): array
    {
        return [
            'live_channel_id' => LiveChannel::factory(),
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'sort_order' => 1,
        ];
    }
}
