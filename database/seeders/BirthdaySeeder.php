<?php

namespace Database\Seeders;

use App\Models\Birthday;
use Illuminate\Database\Seeder;

class BirthdaySeeder extends Seeder
{
    public function run(): void
    {
        Birthday::firstOrCreate(
            ['employee_name' => 'Karyawan Contoh'],
            [
                'division' => 'Divisi Contoh',
                'birth_date' => '2000-'.today()->format('m-d'),
                'image' => null,
                'is_active' => true,
            ],
        );
    }
}
