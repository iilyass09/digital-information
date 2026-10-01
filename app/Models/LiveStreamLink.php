<?php

namespace App\Models;

use Database\Factories\LiveStreamLinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'url', 'logo', 'is_active', 'sort_order'])]
class LiveStreamLink extends Model
{
    /** @use HasFactory<LiveStreamLinkFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function liveHosts(): HasMany
    {
        return $this->hasMany(LiveHost::class);
    }
}
