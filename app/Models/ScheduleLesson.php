<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScheduleLesson extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'version_id',
        'date',
        'lesson_number',
        'shift',
        'group_id',
        'subgroup_id',
        'is_parallel_secondary',
        'discipline_id',
        'lesson_type_id',
        'teacher_id',
        'room_id',
        'building_id',
        'week_type_id',
        'is_auto_generated',
        'is_replacement',
        'original_lesson_id',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_auto_generated' => 'boolean',
            'is_replacement' => 'boolean',
            'is_parallel_secondary' => 'boolean',
            'date' => 'date',
            'lesson_number' => 'integer',
            'shift' => 'integer',
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

    public function subgroup(): BelongsTo
    {
        return $this->belongsTo(Subgroup::class);
    }

    public function discipline(): BelongsTo
    {
        return $this->belongsTo(CurriculumDiscipline::class, 'discipline_id');
    }

    public function lessonType(): BelongsTo
    {
        return $this->belongsTo(LessonType::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function weekType(): BelongsTo
    {
        return $this->belongsTo(WeekType::class);
    }

    public function originalLesson(): BelongsTo
    {
        return $this->belongsTo(__CLASS__, 'original_lesson_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForDate(Builder $query, $date): Builder
    {
        return $query->where('date', $date);
    }

    public function scopeForGroup(Builder $query, $groupId): Builder
    {
        return $query->where('group_id', $groupId);
    }

    public function scopeForTeacher(Builder $query, $teacherId): Builder
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeForRoom(Builder $query, $roomId): Builder
    {
        return $query->where('room_id', $roomId);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereHas('version', function (Builder $q) {
            $q->where('status', 'published');
        });
    }

    public function getTimeStartAttribute(): string
    {
        $bellSchedule = BellSchedule::where('shift_number', $this->shift)
            ->where('lesson_number', $this->lesson_number)
            ->first();

        return $bellSchedule?->time_start ?? '';
    }

    public function getTimeEndAttribute(): string
    {
        $bellSchedule = BellSchedule::where('shift_number', $this->shift)
            ->where('lesson_number', $this->lesson_number)
            ->first();

        return $bellSchedule?->time_end ?? '';
    }
}
