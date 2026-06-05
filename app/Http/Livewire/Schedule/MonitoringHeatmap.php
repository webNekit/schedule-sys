<?php

declare(strict_types=1);

namespace App\Http\Livewire\Schedule;

use App\Models\Group;
use App\Models\ScheduleLesson;
use App\Models\ScheduleVersion;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

class MonitoringHeatmap extends Component
{
    public string $dateFrom;

    public string $dateTo;

    public ?int $departmentId = null;

    public function mount(): void
    {
        $this->dateFrom = Carbon::now()->startOfWeek()->toDateString();
        $this->dateTo = Carbon::now()->endOfWeek()->toDateString();
    }

    public function getGroupsProperty()
    {
        return Group::where('is_active', true)
            ->when($this->departmentId, fn ($q) => $q->where('department_id', $this->departmentId))
            ->orderBy('name')
            ->get();
    }

    public function getDatesProperty()
    {
        Carbon::setLocale('ru');
        $start = Carbon::parse($this->dateFrom);
        $end = Carbon::parse($this->dateTo);
        $dates = [];
        while ($start->lte($end)) {
            $dates[] = $start->toDateString();
            $start->addDay();
        }

        return $dates;
    }

    public function getMatrixProperty()
    {
        $activeVersions = ScheduleVersion::whereIn('status', ['published', 'draft'])->pluck('id')->toArray();
        $publishedVersions = ScheduleVersion::where('status', 'published')->pluck('id')->toArray();

        $lessons = ScheduleLesson::with(['lessonType', 'discipline'])
            ->whereIn('version_id', $activeVersions)
            ->whereBetween('date', [$this->dateFrom, $this->dateTo])
            ->whereIn('status', ['published', 'draft'])
            ->get()
            ->groupBy([
                'group_id',
                fn ($l) => $l->date->format('Y-m-d'),
            ]);

        $matrix = [];
        foreach ($this->groups as $group) {
            $workingDays = array_map('intval', $group->getWorkingDays());

            foreach ($this->dates as $date) {
                $carbonDate = Carbon::parse($date);
                $dayOfWeek = (int) $carbonDate->format('N');

                $dayLessons = $lessons[$group->id][$date] ?? collect();

                if ($dayLessons->contains(fn ($l) => in_array($l->version_id, $publishedVersions))) {
                    $dayLessons = $dayLessons->filter(fn ($l) => in_array($l->version_id, $publishedVersions));
                }

                $count = $dayLessons->count();

                // Проверка на практику (из календаря)
                $isPracticeCalendar = $group->isOnPractice($carbonDate);

                // Проверка на практику (из уроков - если ВСЕ уроки в этот день являются практикой)
                $isPracticeLessons = $count > 0 && $dayLessons->every(function ($l) {
                    $typeCode = $l->lessonType?->code ?? '';
                    $name = $l->discipline?->name ?? '';

                    return in_array($typeCode, ['practice', 'prod_practice', 'edu_practice'])
                        || preg_match('/практика/ui', $name);
                });

                $isPractice = $isPracticeCalendar || $isPracticeLessons;
                $isDayOff = ! in_array($dayOfWeek, $workingDays, true);

                $status = 'empty';
                if ($count > 0) {
                    if ($count < 3) {
                        $status = 'low';
                    } elseif ($count <= 5) {
                        $status = 'normal';
                    } else {
                        $status = 'high';
                    }
                }

                $matrix[$group->id][$date] = [
                    'count' => $count,
                    'status' => $status,
                    'is_practice' => $isPractice,
                    'is_day_off' => $isDayOff,
                ];
            }
        }

        return $matrix;
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.schedule.monitoring-heatmap');
    }
}
