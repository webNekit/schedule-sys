<?php

declare(strict_types=1);

namespace App\Http\Livewire\Schedule;

use App\Models\Group;
use App\Models\Room;
use App\Models\ScheduleLesson;
use App\Models\ScheduleVersion;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Livewire\Component;

class PublicView extends Component
{
    public string $viewMode = 'group';

    public int $viewId = 0;

    public string $weekStart = '';

    public array $scheduleData = [];

    public ?int $versionId = null;

    public function mount(Request $request): void
    {
        $verId = $request->query('version');
        if ($verId) {
            $this->versionId = (int) $verId;
            $ver = ScheduleVersion::find($this->versionId);
            if ($ver) {
                $this->weekStart = $ver->date_from instanceof Carbon
                    ? Carbon::parse($ver->date_from)->startOfWeek(Carbon::MONDAY)->format('Y-m-d')
                    : Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
            }
        }

        $qMode = $request->query('viewMode');
        if ($qMode && in_array($qMode, ['group', 'teacher', 'room'], true)) {
            $this->viewMode = $qMode;
        }

        $qId = $request->query('viewId');
        if ($qId) {
            $this->viewId = (int) $qId;
        }

        if ($this->weekStart === '') {
            $this->weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        }

        $this->loadWeek();
    }

    public function loadWeek(): void
    {
        $weekStart = Carbon::parse($this->weekStart);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $lessons = ScheduleLesson::with(['group', 'discipline', 'teacher', 'room.building', 'lessonType'])
            ->where('date', '>=', $weekStart->format('Y-m-d'))
            ->where('date', '<=', $weekEnd->format('Y-m-d'))
            ->when($this->versionId, fn ($q) => $q->where('version_id', $this->versionId))
            ->when(
                $this->viewMode === 'group' && $this->viewId > 0,
                fn ($q) => $q->where('group_id', $this->viewId),
            )
            ->when(
                $this->viewMode === 'teacher' && $this->viewId > 0,
                fn ($q) => $q->where('teacher_id', $this->viewId),
            )
            ->when(
                $this->viewMode === 'room' && $this->viewId > 0,
                fn ($q) => $q->where('room_id', $this->viewId),
            )
            ->orderBy('group_id')
            ->orderBy('date')
            ->orderBy('lesson_number')
            ->get();

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
        $gk = $this->viewMode === 'group' ? 'group_id' : ($this->viewMode === 'teacher' ? 'teacher_id' : 'room_id');
        $grouped = collect($this->scheduleData)->groupBy($gk);

        return view('livewire.schedule.public-view', [
            'grouped' => $grouped,
            'groups' => Group::active()->orderBy('name')->get(),
            'teachers' => Teacher::active()->orderBy('last_name')->get(),
            'rooms' => Room::active()->orderBy('name')->get(),
            'version' => $this->versionId ? ScheduleVersion::find($this->versionId) : ScheduleVersion::where('status', 'published')->latest()->first(),
        ])->layout('components.layouts.public');
    }
}
