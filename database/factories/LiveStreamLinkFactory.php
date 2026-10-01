<?php

namespace Database\Factories;

use App\Models\LiveStreamLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LiveStreamLink>
 */
class LiveStreamLinkFactory extends Factory
{
    protected $model = LiveStreamLink::class;

    public function definition(): array
    {
        return [
            'name' => 'Slot '.fake()->unique()->numberBetween(1, 9999),
            'url' => 'https://www.tiktok.com/live/'.fake()->unique()->regexify('[a-z0-9]{16}'),
            'logo' => null,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
