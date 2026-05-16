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
        'study_years_9',
        'study_years_11',
        'base_education',
        'form_of_study',
        'max_courses',
        'budget_places',
        'commercial_places',
        'is_active',
        'study_duration',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'study_years' => 'integer',
            'study_months' => 'integer',
            'max_courses' => 'integer',
            'budget_places' => 'integer',
            'commercial_places' => 'integer',
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

    /**
     * Мутатор: автоматически срабатывает при сохранении $specialty->study_duration = '3,9'
     * Разбивает строку на годы и месяцы и раскладывает по нужным колонкам БД.
     */
    public function setStudyDurationAttribute($value): void
    {
        if (empty($value)) {
            $this->attributes['study_years'] = 0;
            $this->attributes['study_months'] = 0;

            return;
        }

        // Поддерживаем как запятую, так и точку при вводе
        $value = str_replace('.', ',', (string) $value);
        $parts = explode(',', $value);

        $this->attributes['study_years'] = (int) ($parts[0] ?? 0);
        $this->attributes['study_months'] = (int) ($parts[1] ?? 0);
    }

    /**
     * Аксессор: автоматически собирает обратно годы и месяцы в строку "3,9" для вывода в форму
     */
    public function getStudyDurationAttribute(): string
    {
        $years = $this->study_years ?? 0;
        $months = $this->study_months ?? 0;

        return $months > 0 ? "{$years},{$months}" : (string) $years;
    }
}
