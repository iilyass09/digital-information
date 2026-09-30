<?php

namespace Database\Factories;

use App\Models\Promotion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Promotion>
 */
class PromotionFactory extends Factory
{
    protected $model = Promotion::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'image' => null,
            'description' => fake()->optional()->paragraph(),
            'start_date' => today(),
            'end_date' => today()->addDays(30),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
