<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Schedule\HoursTrackingService;
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

    public function revertToDraft(): void
    {
        DB::transaction(function () {
            $this->update([
                'status' => 'draft',
                'published_at' => null,
                'published_by' => null,
            ]);

            // Удаляем все записи о выданных часах для этой версии
            HoursTracking::whereHas('scheduleLesson', function ($query) {
                $query->where('version_id', $this->id);
            })->delete();
        });
    }

    public function trackHours(): void
    {
        $academicYearId = $this->academic_year_id ?? AcademicYear::where('is_current', true)->value('id');

        $lessons = $this->lessons()
            ->with(['version', 'group'])
            ->where('status', '!=', 'cancelled')
            ->get();

        $hoursTracking = app(HoursTrackingService::class);

        foreach ($lessons as $lesson) {
            $hoursTracking->trackLesson($lesson, $academicYearId);
        }
    }

    public function untrackLesson(ScheduleLesson $lesson): void
    {
        HoursTracking::where('schedule_lesson_id', $lesson->id)->delete();
    }
}
