<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeacherPosition extends Model
{
    protected $fillable = [
        'name',
        'short_name',
        'category',
        'max_hours_per_week',
    ];

    protected function casts(): array
    {
        return [
            'max_hours_per_week' => 'integer',
        ];
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class);
    }
}
