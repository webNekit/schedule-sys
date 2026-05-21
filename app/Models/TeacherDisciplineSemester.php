<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherDisciplineSemester extends Model
{
    protected $fillable = [
        'teacher_discipline_id',
        'curriculum_semester_id',
        'group_id',
        'planned_hours',
        'actual_hours',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'planned_hours' => 'integer',
            'actual_hours' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function teacherDiscipline(): BelongsTo
    {
        return $this->belongsTo(TeacherDiscipline::class);
    }

    public function curriculumSemester(): BelongsTo
    {
        return $this->belongsTo(CurriculumSemester::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
