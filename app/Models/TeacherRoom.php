<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherRoom extends Model
{
    protected $fillable = [
        'teacher_id',
        'room_id',
        'priority',
        'is_personal',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'is_personal' => 'boolean',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
