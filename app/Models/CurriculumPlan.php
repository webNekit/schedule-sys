<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CurriculumPlan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'specialty_id',
        'academic_year_id',
        'name',
        'version',
        'xml_file_path',
        'excel_file_path',
        'xml_original',
        'parsed_at',
        'total_hours',
        'contact_hours',
        'self_study_hours',
        'practice_hours',
        'is_active',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'parsed_at' => 'datetime',
            'total_hours' => 'integer',
            'contact_hours' => 'integer',
            'self_study_hours' => 'integer',
            'practice_hours' => 'integer',
        ];
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function disciplines(): HasMany
    {
        return $this->hasMany(CurriculumDiscipline::class);
    }

    public function groupAssignments(): HasMany
    {
        return $this->hasMany(GroupCurriculumAssignment::class);
    }

    public function practices(): HasMany
    {
        return $this->hasMany(CurriculumPractice::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}