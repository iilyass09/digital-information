<?php

namespace Database\Seeders;

use App\Models\LiveChannel;
use Illuminate\Database\Seeder;

class LiveChannelSeeder extends Seeder
{
    public function run(): void
    {
        $channels = [
            'Johen PUBG',
            'Johen MLBB',
            'Johen Roblox',
            'Johen Valorant',
            'Johen Free Fire',
            'Johen E-Football',
            'Johen FC Mobile',
            'Monkey PUBG',
        ];

        foreach ($channels as $index => $name) {
            LiveChannel::firstOrCreate(
                ['name' => $name],
                ['is_active' => true, 'sort_order' => $index + 1],
            );
        }
    }
}
