<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BellSchedule extends Model
{
    protected $fillable = [
        'name',
        'shift_number',
        'lesson_number',
        'time_start',
        'time_end',
        'break_after_minutes',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'shift_number' => 'integer',
            'lesson_number' => 'integer',
            'break_after_minutes' => 'integer',
            'sort_order' => 'integer',
            'time_start' => 'datetime:H:i',
            'time_end' => 'datetime:H:i',
        ];
    }
}
