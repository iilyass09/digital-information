<?php

namespace Tests\Feature\Models;

use App\Models\Achievement;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AchievementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_achievement_persists_employee_fields_and_date_cast(): void
    {
        $achievement = Achievement::factory()->create([
            'employee_name' => 'Nama Contoh',
            'division' => 'IT',
            'title' => 'Employee of the Month',
            'achievement_date' => '2026-01-20',
            'is_active' => false,
            'sort_order' => 2,
        ]);

        $this->assertSame('2026-01-20', $achievement->achievement_date->toDateString());
        $this->assertSame(false, $achievement->is_active);
        $this->assertDatabaseHas('achievements', [
            'id' => $achievement->id,
            'employee_name' => 'Nama Contoh',
            'division' => 'IT',
            'title' => 'Employee of the Month',
            'sort_order' => 2,
        ]);
    }
}
