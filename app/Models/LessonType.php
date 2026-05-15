<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonType extends Model
{
    protected $fillable = [
        'name',
        'short_name',
        'code',
        'color',
        'icon',
        'requires_lab',
        'is_control_form',
        'hours_coefficient',
    ];

    protected function casts(): array
    {
        return [
            'requires_lab' => 'boolean',
            'is_control_form' => 'boolean',
            'hours_coefficient' => 'decimal:2',
        ];
    }
}
