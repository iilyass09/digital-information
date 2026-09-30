<?php

namespace Database\Factories;

use App\Models\Achievement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Achievement>
 */
class AchievementFactory extends Factory
{
    protected $model = Achievement::class;

    public function definition(): array
    {
        return [
            'employee_name' => fake()->name(),
            'division' => fake()->jobTitle(),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'image' => null,
            'achievement_date' => today(),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
