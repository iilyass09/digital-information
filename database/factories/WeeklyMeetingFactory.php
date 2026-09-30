<?php

namespace Database\Factories;

use App\Models\WeeklyMeeting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WeeklyMeeting>
 */
class WeeklyMeetingFactory extends Factory
{
    protected $model = WeeklyMeeting::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->name(),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
