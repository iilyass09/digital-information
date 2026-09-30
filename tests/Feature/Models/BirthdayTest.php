<?php

namespace Tests\Feature\Models;

use App\Models\Birthday;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class BirthdayTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_birthday_persists_a_reusable_birth_date_and_active_cast(): void
    {
        $birthday = Birthday::factory()->create([
            'employee_name' => 'Nama Contoh',
            'division' => 'IT',
            'birth_date' => '1996-09-30',
            'is_active' => true,
        ]);

        $this->assertSame('1996-09-30', $birthday->birth_date->toDateString());
        $this->assertSame(true, $birthday->is_active);
        $this->assertDatabaseHas('birthdays', [
            'id' => $birthday->id,
            'employee_name' => 'Nama Contoh',
            'birth_date' => '1996-09-30',
        ]);
    }
}
