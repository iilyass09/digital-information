<?php

namespace App\Models;

use Database\Factories\WeeklyMeetingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'photo', 'is_active', 'sort_order'])]
class WeeklyMeeting extends Model
{
    /** @use HasFactory<WeeklyMeetingFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
