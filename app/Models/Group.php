<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Group extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'specialty_id',
        'department_id',
        'academic_year_id',
        'name',
        'short_name',
        'current_course',
        'students_count',
        'shift',
        'status',
        'enrollment_date',
        'graduation_date',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'current_course' => 'integer',
            'students_count' => 'integer',
            'shift' => 'integer',
            'enrollment_date' => 'date',
            'graduation_date' => 'date',
        ];
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function subgroups(): HasMany
    {
        return $this->hasMany(Subgroup::class);
    }

    public function groupBuildings(): HasMany
    {
        return $this->hasMany(GroupBuilding::class);
    }

    public function buildings(): BelongsToMany
    {
        return $this->belongsToMany(Building::class, 'group_buildings')
            ->withPivot('is_primary', 'notes')
            ->withTimestamps();
    }

    public function scheduleLessons(): HasMany
    {
        return $this->hasMany(ScheduleLesson::class);
    }

    public function curriculumAssignments(): HasMany
    {
        return $this->hasMany(GroupCurriculumAssignment::class);
    }

    public function curriculumPlans(): BelongsToMany
    {
        return $this->belongsToMany(CurriculumPlan::class, 'group_curriculum_assignments')
            ->withPivot('assigned_at', 'assigned_by', 'is_active', 'notes')
            ->withTimestamps();
    }

    public function dayBuildings(): HasMany
    {
        return $this->hasMany(GroupDayBuilding::class);
    }

    public function hoursTrackings(): HasMany
    {
        return $this->hasMany(HoursTracking::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByCourse(Builder $query, int $course): Builder
    {
        return $query->where('current_course', $course);
    }

    public function scopeByShift(Builder $query, int $shift): Builder
    {
        return $query->where('shift', $shift);
    }

    public function scopeFirstShift(Builder $query): Builder
    {
        return $query->where('shift', 1);
    }

    public function scopeSecondShift(Builder $query): Builder
    {
        return $query->where('shift', 2);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->name} ({$this->current_course} курс)";
    }

    public function calculateCurrentCourse(): int
    {
        $currentYear = AcademicYear::where('is_current', true)->first();
        if (!$currentYear || !$this->enrollment_date) {
            return $this->current_course;
        }

        $enrollmentYear = (int) Carbon::parse($this->enrollment_date)->format('Y');
        $course = $currentYear->year_start - $enrollmentYear + 1;

        return max(1, $course);
    }

    public function getCurrentSemester(): int
    {
        $now = Carbon::now();
        $yearStart = $this->academicYear?->date_start
            ? Carbon::parse($this->academicYear->date_start)
            : Carbon::createFromDate($now->year, 9, 1);

        $isFirstSemester = $now->lessThan($yearStart->copy()->addMonths(6));

        return $isFirstSemester
            ? $this->current_course * 2 - 1
            : $this->current_course * 2;
    }

    public function getWorkingDays(): array
    {
        $key = $this->current_course <= 2 ? 'working_days_course_1_2' : 'working_days_course_3_4';
        $setting = SystemSetting::where('key', $key)->first();

        if ($setting && $setting->value) {
            $days = json_decode($setting->value, true);

            if (is_array($days) && $days !== []) {
                return $days;
            }
        }

        return $this->current_course <= 2 ? [1, 2, 3, 4, 5] : [2, 3, 4, 5, 6];
    }

    public function getAllowedLessonNumbers(): array
    {
        $key = 'lesson_numbers_course_' . $this->current_course;
        $setting = SystemSetting::where('key', $key)->first();

        if ($setting && $setting->value) {
            $numbers = json_decode($setting->value, true);

            if (is_array($numbers) && $numbers !== []) {
                return $numbers;
            }
        }

        return [];
    }

    public function promote(): void
    {
        $this->increment('current_course');
    }

    public function graduate(): void
    {
        $this->update(['status' => 'graduated']);
    }

    public function isOnPractice(Carbon $date): bool
    {
        $assignment = $this->curriculumAssignments()->where('is_active', true)->first();
        if (!$assignment) {
            return false;
        }

        return \App\Models\CurriculumPractice::where('curriculum_plan_id', $assignment->curriculum_plan_id)
            ->where('course_number', $this->current_course)
            ->where('start_date', '<=', $date->format('Y-m-d'))
            ->where('end_date', '>=', $date->format('Y-m-d'))
            ->exists();
    }
}