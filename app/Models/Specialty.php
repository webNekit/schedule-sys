<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Specialty extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'department_id',
        'code',
        'name',
        'short_name',
        'qualification',
        'education_level_id',
        'study_years',
        'study_months',
        'base_education',
        'form_of_study',
        'max_courses',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'study_years' => 'integer',
            'study_months' => 'integer',
            'max_courses' => 'integer',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function educationLevel(): BelongsTo
    {
        return $this->belongsTo(EducationLevel::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    public function curriculumPlans(): HasMany
    {
        return $this->hasMany(CurriculumPlan::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
