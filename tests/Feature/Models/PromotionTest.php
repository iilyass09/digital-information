<?php

namespace Tests\Feature\Models;

use App\Models\Promotion;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PromotionTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_promotion_persists_nullable_image_description_and_date_casts(): void
    {
        $promotion = Promotion::factory()->create([
            'title' => 'Periode Contoh',
            'image' => null,
            'description' => null,
            'start_date' => '2026-01-10',
            'end_date' => '2026-01-31',
        ]);

        $this->assertSame('2026-01-10', $promotion->start_date->toDateString());
        $this->assertSame('2026-01-31', $promotion->end_date->toDateString());
        $this->assertSame(true, $promotion->is_active);
        $this->assertDatabaseHas('promotions', [
            'id' => $promotion->id,
            'title' => 'Periode Contoh',
            'image' => null,
            'description' => null,
        ]);
    }
}
