<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleConflict extends Model
{
    protected $fillable = [
        'version_id',
        'conflict_type',
        'severity',
        'date',
        'lesson_number',
        'group_id',
        'teacher_id',
        'room_id',
        'discipline_id',
        'description',
        'suggestion',          // <-- ДОБАВЛЕНО
        'is_resolved',
        'resolved_by',
        'resolved_at',
        'resolution_notes',
    ];

    protected function casts(): array
    {
        return [
            'is_resolved' => 'boolean',
            'date' => 'date',
            'lesson_number' => 'integer',
            'resolved_at' => 'datetime',
        ];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(ScheduleVersion::class, 'version_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function discipline(): BelongsTo
    {
        return $this->belongsTo(CurriculumDiscipline::class, 'discipline_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
