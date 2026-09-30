<?php

namespace Database\Factories;

use App\Models\LiveChannel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LiveChannel>
 */
class LiveChannelFactory extends Factory
{
    protected $model = LiveChannel::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
