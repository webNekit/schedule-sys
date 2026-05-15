<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Teacher extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'department_id',
        'position_id',
        'last_name',
        'first_name',
        'middle_name',
        'full_name',
        'short_name',
        'phone',
        'email',
        'internal_phone',
        'employment_type',
        'rate',
        'max_hours_per_week',
        'min_lessons_per_day',
        'max_lessons_per_day',
        'has_methodical_day',
        'methodical_day_of_week',
        'qualification_category',
        'academic_degree',
        'hire_date',
        'is_active',
        'notes',
        'working_days',
        'working_lesson_numbers',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'rate' => 'decimal:2',
            'working_days' => 'array',
            'working_lesson_numbers' => 'array',
            'max_hours_per_week' => 'integer',
            'min_lessons_per_day' => 'integer',
            'max_lessons_per_day' => 'integer',
            'has_methodical_day' => 'boolean',
            'methodical_day_of_week' => 'integer',
            'hire_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(TeacherPosition::class, 'position_id');
    }

    public function disciplines(): HasMany
    {
        return $this->hasMany(TeacherDiscipline::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(TeacherRoom::class);
    }

    public function buildings(): HasMany
    {
        return $this->hasMany(TeacherBuilding::class);
    }

    public function unavailabilities(): HasMany
    {
        return $this->hasMany(TeacherUnavailability::class);
    }

    public function dayBuildings(): HasMany
    {
        return $this->hasMany(TeacherDayBuilding::class);
    }

    public function scheduleLessons(): HasMany
    {
        return $this->hasMany(ScheduleLesson::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getShortNameAttribute(): string
    {
        if ($this->attributes['short_name'] ?? null) {
            return $this->attributes['short_name'];
        }

        $firstNameInitial = $this->first_name ? mb_substr($this->first_name, 0, 1).'.' : '';
        $middleNameInitial = $this->middle_name ? mb_substr($this->middle_name, 0, 1).'.' : '';

        return "{$this->last_name} {$firstNameInitial}{$middleNameInitial}";
    }

    public function isAvailableOn($date, $lessonNumber): bool
    {
        return true;
    }

    public function getBuildingForDate($date): ?Building
    {
        $dayBuilding = $this->dayBuildings()
            ->where('date', $date)
            ->first();

        return $dayBuilding?->building;
    }
}
