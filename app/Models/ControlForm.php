<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ControlForm extends Model
{
    protected $fillable = [
        'name',
        'short_name',
        'code',
        'is_exam_session',
    ];

    protected function casts(): array
    {
        return [
            'is_exam_session' => 'boolean',
        ];
    }
}
