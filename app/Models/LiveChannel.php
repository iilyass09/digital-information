<?php

namespace App\Models;

use Database\Factories\LiveChannelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'logo', 'stream_url', 'stream_logo', 'is_active', 'sort_order'])]
class LiveChannel extends Model
{
    /** @use HasFactory<LiveChannelFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function liveSchedules(): HasMany
    {
        return $this->hasMany(LiveSchedule::class);
    }
}
