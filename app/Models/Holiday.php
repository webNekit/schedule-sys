<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = [
        'date',
        'name',
        'type',
        'description',
        'year',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'year' => 'integer',
        ];
    }
}
