<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HoursTracking extends Model
{
    protected $table = 'hours_tracking';

    protected $fillable = [
        'group_id',
        'discipline_id',
        'teacher_id',
        'semester_id',
        'academic_year_id',
        'lesson_type_id',
        'date',
        'hours_conducted',
        'counts_for_group',
        'schedule_lesson_id',
        'is_cancelled',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'hours_conducted' => 'decimal:2',
            'counts_for_group' => 'boolean',
            'is_cancelled' => 'boolean',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function discipline(): BelongsTo
    {
        return $this->belongsTo(CurriculumDiscipline::class, 'discipline_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(CurriculumSemester::class, 'semester_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function lessonType(): BelongsTo
    {
        return $this->belongsTo(LessonType::class);
    }

    public function scheduleLesson(): BelongsTo
    {
        return $this->belongsTo(ScheduleLesson::class);
    }
}
