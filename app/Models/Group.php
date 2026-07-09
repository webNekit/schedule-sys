<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Group extends Model
{
    use HasFactory, SoftDeletes;

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
        'enrollment_year',
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

    public function sportComplexSlot(): HasOne
    {
        return $this->hasOne(SportComplexSlot::class);
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

    public function getCurriculumAssignmentForDate(?Carbon $date = null): ?GroupCurriculumAssignment
    {
        $date = $date ?? Carbon::now();

        $academicYear = AcademicYear::where('date_start', '<=', $date->toDateString())
            ->where('date_end', '>=', $date->toDateString())
            ->first() ?? AcademicYear::where('is_current', true)->first();

        if ($academicYear) {
            $assignment = $this->curriculumAssignments()
                ->where('academic_year_id', $academicYear->id)
                ->where('is_active', true)
                ->first();

            if ($assignment) {
                return $assignment;
            }
        }

        return $this->curriculumAssignments()->where('is_active', true)->first();
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
        if ($this->status === 'graduated') {
            return "{$this->name} (выпущена)";
        }

        return "{$this->name} ({$this->current_course} курс)";
    }

    public function calculateCurrentCourse(): int
    {
        $currentYear = AcademicYear::where('is_current', true)->first();
        if (! $currentYear || ! $this->enrollment_date) {
            return $this->current_course;
        }

        $enrollmentYear = (int) Carbon::parse($this->enrollment_date)->format('Y');
        $course = $currentYear->year_start - $enrollmentYear + 1;

        return max(1, $course);
    }

    public function getCurrentSemester(?Carbon $date = null): int
    {
        $date = $date ?? Carbon::now();

        // Пытаемся найти учебный год, который охватывает данную дату.
        // Если привязанный к группе год не подходит, ищем в базе текущий или подходящий по датам.
        $academicYear = $this->academicYear;
        if (! $academicYear || $date->lessThan(Carbon::parse($academicYear->date_start)) || $date->greaterThan(Carbon::parse($academicYear->date_end))) {
            $academicYear = AcademicYear::where('date_start', '<=', $date->toDateString())
                ->where('date_end', '>=', $date->toDateString())
                ->first() ?? AcademicYear::where('is_current', true)->first() ?? $this->academicYear;
        }

        $yearStart = $academicYear?->date_start
            ? Carbon::parse($academicYear->date_start)
            : Carbon::createFromDate($date->year, 9, 1);

        $isFirstSemester = $date->lessThan($yearStart->copy()->addMonths(6));

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
        $key = 'lesson_numbers_course_'.$this->current_course;
        $setting = SystemSetting::where('key', $key)->first();

        if ($setting && $setting->value) {
            $numbers = json_decode($setting->value, true);

            if (is_array($numbers) && $numbers !== []) {
                $firstKey = array_key_first($numbers);

                if (is_array($numbers[$firstKey])) {
                    return $numbers;
                }

                return $numbers;
            }
        }

        return [];
    }

    public function getAllowedLessonNumbersForDay(int $dayOfWeek): array
    {
        $all = $this->getAllowedLessonNumbers();

        if (is_array($all) && isset($all[$dayOfWeek]) && is_array($all[$dayOfWeek])) {
            return array_values($all[$dayOfWeek]);
        }

        return [];
    }

    public function getWeeklyHours(): int
    {
        $setting = SystemSetting::where('key', 'weekly_hours_total')->first();

        return $setting ? (int) $setting->value : 36;
    }

    public function getWeeklyPairs(): int
    {
        return (int) ceil($this->getWeeklyHours() / 2);
    }

    protected static function booted(): void
    {
        static::saving(function (Group $group) {
            $maxCourses = $group->specialty?->max_courses ?? 4;

            if ($group->current_course > $maxCourses) {
                // Курс превысил максимум для специальности — группа выпущена.
                $group->status = 'graduated';
            } elseif (
                $group->status === 'graduated'
                && $group->isDirty('current_course')
                && (int) $group->getOriginal('current_course') > $maxCourses
            ) {
                // Курс выпущенной (по «переполнению») группы вручную понизили обратно
                // в диапазон обучения — реактивируем. Группу, выпущенную явно на
                // последнем курсе, это не трогает.
                $group->status = 'active';
            }
        });
    }

    public function promote(): void
    {
        $this->current_course++;
        $this->save();
    }

    public function graduate(): void
    {
        // Выпуск означает завершение последнего курса: уводим курс за максимум,
        // чтобы статус «graduated» был согласован и не сбрасывался хуком saving.
        $maxCourses = $this->specialty?->max_courses ?? 4;
        if ($this->current_course <= $maxCourses) {
            $this->current_course = $maxCourses + 1;
        }
        $this->status = 'graduated';
        $this->save();
    }

    /**
     * Проверяет, может ли группа заниматься на указанной паре в указанную дату (проверка смены/графика).
     */
    public function isAvailableOn($date, $lessonNumber): bool
    {
        $parsedDate = $date instanceof Carbon ? $date : Carbon::parse($date);
        $dayOfWeek = $parsedDate->dayOfWeekIso;

        // 1. Проверка разрешенных пар для данного дня (из настроек системы)
        $allowedNumbers = $this->getAllowedLessonNumbersForDay($dayOfWeek);

        // Если настройки заданы, проверяем вхождение
        if (! empty($allowedNumbers)) {
            return in_array($lessonNumber, $allowedNumbers, true);
        }

        // Если настройки НЕ заданы, используем дефолтную логику смен
        $defaultAllowed = $this->shift === 1 ? range(1, 5) : range(3, 7);

        return in_array($lessonNumber, $defaultAllowed, true);
    }

    public function isOnPractice(Carbon $date): bool
    {
        $block = $this->getCalendarBlock($date);

        // Экзаменационная сессия — не практика: внутри неё экзамены ставятся
        // по точным датам, а не блоком-маркером на всю неделю.
        return $block !== null && $block->type !== 'exam_session';
    }

    public function getCalendarBlock(Carbon $date): ?CurriculumPractice
    {
        $assignment = $this->getCurriculumAssignmentForDate($date);
        if (! $assignment) {
            return null;
        }

        $practices = CurriculumPractice::where('curriculum_plan_id', $assignment->curriculum_plan_id)
            ->where('course_number', $this->current_course)
            ->get();

        foreach ($practices as $p) {
            $pStart = Carbon::parse($p->start_date);
            $pEnd = Carbon::parse($p->end_date);

            $pYearOffset = ($pStart->month < 9) ? $pStart->year - 1 : $pStart->year;
            $dYearOffset = ($date->month < 9) ? $date->year - 1 : $date->year;
            $yearDiff = $dYearOffset - $pYearOffset;

            $normalizedStart = $pStart->copy()->addYears($yearDiff);
            $normalizedEnd = $pEnd->copy()->addYears($yearDiff);

            if ($date->between($normalizedStart, $normalizedEnd)) {
                return $p;
            }
        }

        return null;
    }
}
