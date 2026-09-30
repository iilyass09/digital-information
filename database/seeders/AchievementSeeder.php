<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    public function run(): void
    {
        Achievement::firstOrCreate(
            [
                'employee_name' => 'Karyawan Contoh',
                'title' => 'Achievement Contoh',
            ],
            [
                'division' => 'Divisi Contoh',
                'description' => 'Data contoh untuk lingkungan development.',
                'image' => null,
                'achievement_date' => today(),
                'is_active' => true,
                'sort_order' => 1,
            ],
        );
    }
}
