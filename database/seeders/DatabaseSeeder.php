<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            LiveChannelSeeder::class,
        ]);

        if (app()->environment('local', 'testing')) {
            $this->call([
                PromotionSeeder::class,
                AchievementSeeder::class,
                HostSeeder::class,
                LiveScheduleSeeder::class,
                LiveHostSeeder::class,
                BirthdaySeeder::class,
                WeeklyMeetingSeeder::class,
            ]);
        }
    }
}
