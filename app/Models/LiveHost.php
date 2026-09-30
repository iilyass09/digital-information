<?php

namespace App\Models;

use Database\Factories\LiveHostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['live_schedule_id', 'host_id', 'date', 'is_active'])]
class LiveHost extends Model
{
    /** @use HasFactory<LiveHostFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function liveSchedule(): BelongsTo
    {
        return $this->belongsTo(LiveSchedule::class);
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(Host::class);
    }
}
