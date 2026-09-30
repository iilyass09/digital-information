<?php

namespace Database\Seeders;

use App\Models\Host;
use App\Models\LiveChannel;
use App\Models\LiveHost;
use Illuminate\Database\Seeder;

class LiveHostSeeder extends Seeder
{
    public function run(): void
    {
        $roster = [
            'Johen PUBG' => ['Fathan', 'Rafly', 'Yogi', 'Eben'],
            'Johen MLBB' => ['Dhika', 'Bachtiar', 'Ridwan', 'Immanuel'],
            'Johen E-Football' => ['Rizal', 'Fajar', 'Ilham', 'Bilal'],
            'Johen FC Mobile' => ['Akbar', 'Alya', 'Fikri', 'Rifki'],
            'Johen Free Fire' => ['Maul', 'Selvi', 'Pratiwi', 'Rian'],
            'Johen Valorant' => ['Jessica', 'Davi', 'Firdaus', 'Dafa'],
            'Johen Roblox' => ['Fathir', 'Fabio', 'Revival', 'Azzam'],
            'Monkey PUBG' => ['Georde', 'Rasendria', 'Yayan', 'Raiya'],
        ];

        $date = today()->toDateString();

        LiveChannel::query()->orderBy('sort_order')->each(function (LiveChannel $channel) use ($roster, $date): void {
            $names = $roster[$channel->name] ?? [];

            $channel->liveSchedules()->orderBy('sort_order')->get()
                ->each(function ($schedule, $index) use ($names, $date): void {
                    $host = isset($names[$index]) ? Host::query()->where('name', $names[$index])->first() : null;

                    LiveHost::updateOrCreate(
                        ['live_schedule_id' => $schedule->id, 'date' => $date],
                        ['host_id' => $host?->id, 'is_active' => true],
                    );
                });
        });
    }
}
