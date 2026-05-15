<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurriculumSemester extends Model
{
    protected $fillable = [
        'discipline_id',
        'course_number',
        'semester_number',
        'semester_in_course',
        'hours_total',
        'hours_lecture',
        'hours_practice',
        'hours_lab',
        'hours_self_study',
        'hours_consultation',
        'control_form_id',
        'exam_hours',
        'course_project_hours',
        'weeks_count',
        'hours_per_week',
    ];

    protected function casts(): array
    {
        return [
            'course_number' => 'integer',
            'semester_number' => 'integer',
            'semester_in_course' => 'integer',
            'hours_total' => 'integer',
            'hours_lecture' => 'integer',
            'hours_practice' => 'integer',
            'hours_lab' => 'integer',
            'hours_self_study' => 'integer',
            'hours_consultation' => 'integer',
            'exam_hours' => 'integer',
            'course_project_hours' => 'integer',
            'weeks_count' => 'integer',
            'hours_per_week' => 'decimal:2',
        ];
    }

    public function discipline(): BelongsTo
    {
        return $this->belongsTo(CurriculumDiscipline::class, 'discipline_id');
    }

    public function controlForm(): BelongsTo
    {
        return $this->belongsTo(ControlForm::class);
    }
}
