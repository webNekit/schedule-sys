<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'is_editable',
    ];

    protected function casts(): array
    {
        return [
            'is_editable' => 'boolean',
        ];
    }
}
