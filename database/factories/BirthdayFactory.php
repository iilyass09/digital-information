<?php

namespace Database\Factories;

use App\Models\Birthday;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Birthday>
 */
class BirthdayFactory extends Factory
{
    protected $model = Birthday::class;

    public function definition(): array
    {
        return [
            'employee_name' => fake()->name(),
            'division' => fake()->jobTitle(),
            'birth_date' => fake()->dateTimeBetween('-65 years', '-18 years')->format('Y-m-d'),
            'image' => null,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
