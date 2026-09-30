<?php

namespace Database\Seeders;

use App\Models\Promotion;
use Illuminate\Database\Seeder;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        Promotion::firstOrCreate(
            ['title' => 'Contoh Promosi Phase 2'],
            [
                'image' => null,
                'description' => 'Data contoh untuk lingkungan development.',
                'start_date' => today(),
                'end_date' => today()->addDays(30),
                'is_active' => true,
                'sort_order' => 1,
            ],
        );
    }
}
