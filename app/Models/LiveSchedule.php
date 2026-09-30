<?php

namespace App\Models;

use Database\Factories\LiveScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['live_channel_id', 'start_time', 'end_time', 'sort_order'])]
class LiveSchedule extends Model
{
    /** @use HasFactory<LiveScheduleFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function liveChannel(): BelongsTo
    {
        return $this->belongsTo(LiveChannel::class);
    }

    public function liveHosts(): HasMany
    {
        return $this->hasMany(LiveHost::class);
    }
}
