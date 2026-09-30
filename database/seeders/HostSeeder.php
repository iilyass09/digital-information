<?php

namespace Database\Seeders;

use App\Models\Host;
use Illuminate\Database\Seeder;

class HostSeeder extends Seeder
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

        $order = 0;

        foreach ($roster as $names) {
            foreach ($names as $name) {
                Host::firstOrCreate(
                    ['name' => $name],
                    ['is_active' => true, 'sort_order' => ++$order],
                );
            }
        }
    }
}
