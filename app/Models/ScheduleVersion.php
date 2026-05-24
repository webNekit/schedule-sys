<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class ScheduleVersion extends Model
{
    protected $fillable = [
        'name',
        'academic_year_id',
        'department_id',
        'date_from',
        'date_to',
        'period_type',
        'status',
        'generation_type',
        'generated_at',
        'published_at',
        'published_by',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date_from' => 'date',
            'date_to' => 'date',
            'generated_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(ScheduleLesson::class, 'version_id');
    }

    public function conflicts(): HasMany
    {
        return $this->hasMany(ScheduleConflict::class, 'version_id');
    }

    public function publish(int $userId): void
    {
        DB::transaction(function () use ($userId) {
            $this->update([
                'status' => 'published',
                'published_at' => now(),
                'published_by' => $userId,
            ]);

            $this->trackHours();
        });
    }

    public function trackHours(): void
    {
        $lessons = $this->lessons()
            ->with('version')
            ->where('status', '!=', 'cancelled')
            ->get();

        foreach ($lessons as $lesson) {
            $academicYearId = $this->academic_year_id ?? AcademicYear::where('is_current', true)->first()?->id;
            if (! $academicYearId || ! $lesson->discipline_id || ! $lesson->teacher_id || ! $lesson->group_id || ! $lesson->lesson_type_id || ! $lesson->date) {
                continue;
            }

            $semesterId = $this->resolveSemesterId(
                $lesson->discipline_id,
                $lesson->date,
                $academicYearId,
            );

            if (! $semesterId) {
                continue;
            }

            $exists = HoursTracking::where('schedule_lesson_id', $lesson->id)->exists();
            if ($exists) {
                continue;
            }

            HoursTracking::create([
                'group_id' => $lesson->group_id,
                'discipline_id' => $lesson->discipline_id,
                'teacher_id' => $lesson->teacher_id,
                'semester_id' => $semesterId,
                'academic_year_id' => $academicYearId,
                'lesson_type_id' => $lesson->lesson_type_id,
                'date' => $lesson->date,
                'hours_conducted' => 2, // 1 пара = 2 часа
                'schedule_lesson_id' => $lesson->id,
                'is_cancelled' => false,
            ]);
        }
    }

    public function untrackLesson(ScheduleLesson $lesson): void
    {
        HoursTracking::where('schedule_lesson_id', $lesson->id)->delete();
    }

    private function resolveSemesterId(?int $disciplineId, mixed $date, ?int $academicYearId): ?int
    {
        if (! $disciplineId || ! $academicYearId) {
            return null;
        }

        $lessonDate = $date instanceof Carbon ? $date : Carbon::parse($date);
        $academicYear = AcademicYear::find($academicYearId);

        if (! $academicYear) {
            return null;
        }

        $semesterInCourse = 2;
        if ($lessonDate->greaterThanOrEqualTo($academicYear->first_semester_start)
            && $lessonDate->lessThanOrEqualTo($academicYear->first_semester_end)) {
            $semesterInCourse = 1;
        } elseif ($lessonDate->greaterThanOrEqualTo($academicYear->second_semester_start)
            && $lessonDate->lessThanOrEqualTo($academicYear->second_semester_end)) {
            $semesterInCourse = 2;
        }

        $cs = CurriculumSemester::where('discipline_id', $disciplineId)
            ->where('semester_in_course', $semesterInCourse)
            ->first();

        return $cs?->id;
    }
}
