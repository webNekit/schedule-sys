<?php

declare(strict_types=1);

namespace App\Http\Livewire\Schedule;

use App\Models\Department;
use App\Models\Group;
use App\Models\ScheduleLesson;
use App\Models\ScheduleVersion;
use Carbon\Carbon;
use Livewire\Component;

class DayShare extends Component
{
    public int $departmentId = 0;

    public string $date = '';

    public array $scheduleData = [];

    public function mount(): void
    {
        $this->departmentId = (int) request()->query('department', 0);
        $this->date = request()->query('date', Carbon::now()->format('Y-m-d'));
        $this->loadDay();
    }

    public function loadDay(): void
    {
        $latestVersion = ScheduleVersion::latest()->first();

        $query = ScheduleLesson::with(['group', 'discipline', 'teacher', 'room.building', 'lessonType'])
            ->whereDate('date', $this->date)
            ->when($latestVersion, fn ($q) => $q->where('version_id', $latestVersion->id));

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
    }

    public function render()
    {
        return view('livewire.schedule.day-share', [
            'department' => Department::find($this->departmentId),
            'date' => $this->date,
        ])->layout('components.layouts.public');
    }
}
