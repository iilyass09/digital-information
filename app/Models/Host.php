<?php

namespace App\Models;

use Database\Factories\HostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'photo', 'is_active', 'sort_order'])]
class Host extends Model
{
    /** @use HasFactory<HostFactory> */
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
