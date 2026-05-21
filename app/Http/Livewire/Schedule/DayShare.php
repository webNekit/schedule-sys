<?php

declare(strict_types=1);

namespace App\Http\Livewire\Schedule;

use App\Models\Department;
use App\Models\Group;
use App\Models\Holiday;
use App\Models\ScheduleLesson;
use App\Models\ScheduleVersion;
use App\Models\Vacation;
use Carbon\Carbon;
use Livewire\Component;

class DayShare extends Component
{
    public int $departmentId = 0;

    public string $date = '';

    public array $scheduleData = [];

    public array $practiceData = [];

    public ?int $versionId = null;

    public function mount(): void
    {
        $this->departmentId = (int) request()->query('department', 0);
        if ($this->departmentId === 0) {
            $this->departmentId = Department::first()?->id ?? 0;
        }
        $this->date = request()->query('date', Carbon::now()->format('Y-m-d'));
        $this->versionId = request()->query('version') ? (int) request()->query('version') : null;
        $this->loadDay();
    }

    public function loadDay(): void
    {
        $version = null;
        if ($this->versionId) {
            $version = ScheduleVersion::find($this->versionId);
        }
        
        if (! $version) {
            $version = ScheduleVersion::where('status', 'published')->latest()->first() 
                      ?? ScheduleVersion::latest()->first();
        }

        $query = ScheduleLesson::with(['group', 'discipline', 'teacher', 'room.building', 'lessonType'])
            ->whereDate('date', $this->date)
            ->when($version, fn ($q) => $q->where('version_id', $version->id));

        if ($this->departmentId > 0) {
            $groupIds = Group::where('department_id', $this->departmentId)->pluck('id');
            $query->whereIn('group_id', $groupIds);
        }

        $lessons = $query->orderBy('group_id')->orderBy('lesson_number')->get();

        $this->scheduleData = $lessons->map(function ($lesson) {
            $data = $lesson->toArray();
            $data['date'] = $lesson->date instanceof Carbon
                ? $lesson->date->format('Y-m-d')
                : $lesson->date;

            return $data;
        })->values()->toArray();

        // Load practice data
        $this->practiceData = [];
        $groupIds = $this->departmentId > 0 
            ? Group::where('department_id', $this->departmentId)->pluck('id')->toArray()
            : Group::active()->pluck('id')->toArray();

        if (!empty($groupIds)) {
            $allPractices = \App\Models\CurriculumPractice::whereIn('curriculum_plan_id', function($q) use ($groupIds) {
                    $q->select('curriculum_plan_id')
                      ->from('group_curriculum_assignments')
                      ->whereIn('group_id', $groupIds)
                      ->where('is_active', true);
                })
                ->get();

            $checkDate = \Carbon\Carbon::parse($this->date);

            foreach ($allPractices as $p) {
                $targetGroupIds = \App\Models\GroupCurriculumAssignment::where('curriculum_plan_id', $p->curriculum_plan_id)
                    ->whereIn('group_id', $groupIds)
                    ->pluck('group_id');
                
                foreach ($targetGroupIds as $gid) {
                    $group = Group::find($gid);
                    if ($group && $group->current_course === $p->course_number) {
                        // Year-agnostic check
                        $pStart = \Carbon\Carbon::parse($p->start_date);
                        $pEnd = \Carbon\Carbon::parse($p->end_date);
                        
                        $pYearOffset = ($pStart->month < 9) ? $pStart->year - 1 : $pStart->year;
                        $dYearOffset = ($checkDate->month < 9) ? $checkDate->year - 1 : $checkDate->year;
                        $yearDiff = $dYearOffset - $pYearOffset;
                        
                        $normalizedStart = $pStart->copy()->addYears($yearDiff);
                        $normalizedEnd = $pEnd->copy()->addYears($yearDiff);

                        if ($checkDate->between($normalizedStart, $normalizedEnd)) {
                            $workingDays = $group->getWorkingDays();
                            if (in_array((int)$checkDate->format('N'), $workingDays, true) && !$this->isNonWorkingDay($checkDate)) {
                                $this->practiceData[$gid] = [
                                    'symbol' => $p->symbol,
                                    'type' => $p->type,
                                ];
                            }
                        }
                    }
                }
            }
        }
    }

    private function isNonWorkingDay(Carbon $date): bool
    {
        if (Holiday::where('date', $date->toDateString())->exists()) {
            return true;
        }

        return Vacation::where('start_date', '<=', $date->toDateString())
            ->where('end_date', '>=', $date->toDateString())
            ->exists();
    }

    public function render()
    {
        return view('livewire.schedule.day-share', [
            'department' => Department::find($this->departmentId),
            'date' => $this->date,
        ])->layout('components.layouts.public');
    }
}
