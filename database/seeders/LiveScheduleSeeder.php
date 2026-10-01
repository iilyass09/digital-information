<?php

namespace Database\Seeders;

use App\Models\LiveChannel;
use App\Models\LiveSchedule;
use Illuminate\Database\Seeder;

class LiveScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $timeSlots = [
            ['07:00:00', '12:00:00'],
            ['13:00:00', '18:00:00'],
            ['19:00:00', '24:00:00'],
            ['01:00:00', '06:00:00'],
        ];

        LiveChannel::query()->orderBy('sort_order')->each(function (LiveChannel $channel) use ($timeSlots): void {
            foreach ($timeSlots as $index => [$startTime, $endTime]) {
                LiveSchedule::firstOrCreate(
                    [
                        'live_channel_id' => $channel->id,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                    ],
                    ['sort_order' => $index + 1],
                );
            }
        });
    }
}
