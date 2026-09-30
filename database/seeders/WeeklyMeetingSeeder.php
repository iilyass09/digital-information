<?php

namespace Database\Seeders;

use App\Models\WeeklyMeeting;
use Illuminate\Database\Seeder;

class WeeklyMeetingSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'Nadia Putri',
            'Dimas Pratama',
            'Raka Firmansyah',
            'Ayu Lestari',
            'Fathan Maulana',
            'Jihan Ramadhani',
            'Fikri Ananda',
            'Salsabila',
        ];

        $order = 0;

        foreach ($names as $name) {
            WeeklyMeeting::firstOrCreate(
                ['name' => $name],
                [
                    'is_active' => true,
                    'sort_order' => ++$order,
                ],
            );
        }
    }
}
