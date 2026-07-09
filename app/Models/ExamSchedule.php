<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamSchedule extends Model
{
    protected $fillable = [
        'curriculum_semester_id',
        'group_id',
        'exam_date',
        'lesson_number',
        'room_id',
        'teacher_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'exam_date' => 'date',
            'lesson_number' => 'integer',
        ];
    }

    public function curriculumSemester(): BelongsTo
    {
        return $this->belongsTo(CurriculumSemester::class, 'curriculum_semester_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
