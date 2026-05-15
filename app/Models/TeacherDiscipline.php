<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeacherDiscipline extends Model
{
    protected $fillable = [
        'teacher_id',
        'discipline_id',
        'group_id',
        'subgroup_id',
        'academic_year_id',
        'is_primary',
        'planned_hours',
        'actual_hours',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'planned_hours' => 'integer',
            'actual_hours' => 'integer',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function discipline(): BelongsTo
    {
        return $this->belongsTo(CurriculumDiscipline::class, 'discipline_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function subgroup(): BelongsTo
    {
        return $this->belongsTo(Subgroup::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function semesters(): HasMany
    {
        return $this->hasMany(TeacherDisciplineSemester::class);
    }
}
