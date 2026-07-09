<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchedulingRule extends Model
{
    protected $fillable = [
        'key',
        'scope',
        'scope_id',
        'is_enabled',
        'severity',
        'params',
    ];

    protected function casts(): array
    {
        return [
            'scope_id' => 'integer',
            'is_enabled' => 'boolean',
            'params' => 'array',
        ];
    }
}
