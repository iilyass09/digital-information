<?php

namespace App\Models;

use Database\Factories\AchievementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['employee_name', 'division', 'title', 'description', 'image', 'achievement_date', 'is_active', 'sort_order'])]
class Achievement extends Model
{
    /** @use HasFactory<AchievementFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'achievement_date' => 'date',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
