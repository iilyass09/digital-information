<?php

namespace App\Models;

use Database\Factories\BirthdayFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['employee_name', 'division', 'birth_date', 'image', 'is_active', 'sort_order'])]
class Birthday extends Model
{
    /** @use HasFactory<BirthdayFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
