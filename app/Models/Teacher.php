<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
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
        $fi = $this->first_name ? mb_substr($this->first_name, 0, 1).'.' : '';
        $mi = $this->middle_name ? mb_substr($this->middle_name, 0, 1).'.' : '';

        return "{$this->last_name} {$fi}{$mi}";
    }

    /**
     * Проверяет, может ли преподаватель вести указанную пару в указанную дату.
     */
    public function isAvailableOn($date, $lessonNumber): bool
    {
        $parsedDate = $date instanceof Carbon ? $date : Carbon::parse($date);
        $dayOfWeek = $parsedDate->dayOfWeekIso;

        // 1. Проверка рабочих дней преподавателя
        if (is_array($this->working_days) && ! in_array($dayOfWeek, $this->working_days, true)) {
            return false;
        }

        // 2. Проверка разрешенных пар (слотов) преподавателя
        if (is_array($this->working_lesson_numbers) && count($this->working_lesson_numbers) > 0) {
            if (isset($this->working_lesson_numbers[$dayOfWeek])) {
                // Если структура ассоциативная по дням недели: ["1" => [3,4,5], "2" => [...]]
                $allowedForDay = $this->working_lesson_numbers[$dayOfWeek];
                if (is_array($allowedForDay) && ! in_array($lessonNumber, $allowedForDay, true)) {
                    return false;
                }
            } else {
                // Если структура плоская (один шаблон на все дни): [3,4,5,6]
                $isAssociative = array_keys($this->working_lesson_numbers) !== range(0, count($this->working_lesson_numbers) - 1);

                if (! $isAssociative && ! in_array($lessonNumber, $this->working_lesson_numbers, true)) {
                    return false;
                } elseif ($isAssociative && ! isset($this->working_lesson_numbers[$dayOfWeek])) {
                    // Если массив ассоциативный, но для текущего дня нет настроек (выходной)
                    return false;
                }
            }
        }

        // 3. Проверка методического дня
        if ($this->has_methodical_day && $this->methodical_day_of_week === $dayOfWeek) {
            return false;
        }

        // 4. Проверка явных заявлений о недоступности (отгулы, больничные)
        $hasUnavailability = $this->unavailabilities()
            ->where('is_approved', true)
            ->where('date_from', '<=', $parsedDate->format('Y-m-d'))
            ->where('date_to', '>=', $parsedDate->format('Y-m-d'))
            ->get()
            ->contains(function (TeacherUnavailability $unavailability) use ($lessonNumber) {
                if ($unavailability->all_day) {
                    return true; // Недоступен весь день
                }

                // Если недоступность на конкретные часы, проверяем пересечение с расписанием звонков
                $bell = BellSchedule::where('lesson_number', $lessonNumber)
                    ->where('is_active', true)
                    ->first();

                if ($bell && $unavailability->time_from && $unavailability->time_to) {
                    $lessonStart = Carbon::parse($bell->time_start);
                    $lessonEnd = Carbon::parse($bell->time_end);
                    $unavailStart = Carbon::parse($unavailability->time_from);
                    $unavailEnd = Carbon::parse($unavailability->time_to);

                    // Проверяем пересечение отрезков времени
                    return $lessonStart->lessThan($unavailEnd) && $lessonEnd->greaterThan($unavailStart);
                }

                return false;
            });

        if ($hasUnavailability) {
            return false;
        }

        return true;
    }

    public function getBuildingForDate($date): ?Building
    {
        $db = $this->dayBuildings()->where('date', $date)->first();

        return $db?->building;
    }
}
