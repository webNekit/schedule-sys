<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurriculumPractice extends Model
{
    protected $fillable = [
        'curriculum_plan_id',
        'teacher_id',
        'course_number',
        'type',
        'symbol',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'course_number' => 'integer',
            'teacher_id' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function curriculumPlan(): BelongsTo
    {
        return $this->belongsTo(CurriculumPlan::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }
}
