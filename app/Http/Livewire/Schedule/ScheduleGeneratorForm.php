<?php

declare(strict_types=1);

namespace App\Http\Livewire\Schedule;

use App\Models\Department;
use App\Models\Group;
use App\Models\ScheduleVersion;
use App\Services\Schedule\ScheduleGeneratorService;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ScheduleGeneratorForm extends Component
{
    #[Rule('required|in:day,week,month')]
    public string $periodType = 'week';

    public string $date = '';

    public string $weekStart = '';

    public int $month = 0;

    public int $year = 0;

    public array $selectedGroups = [];

    public bool $allGroups = true;

    public ?int $departmentId = null;

    public bool $generating = false;

    public ?int $progress = null;

    public ?array $result = null;

    public function mount(): void
    {
        $now = Carbon::now();
        $this->weekStart = $now->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        $this->date = $now->format('Y-m-d');
        $this->month = (int) $now->month;
        $this->year = (int) $now->year;
    }

    public function render(): mixed
    {
        return view('livewire.schedule.schedule-generator-form', [
            'departments' => Department::where('is_active', true)
                ->orderBy('name')
                ->get(),
            'groups' => Group::where('is_active', true)
                ->when(
                    $this->departmentId,
                    fn ($q) => $q->where('department_id', $this->departmentId),
                )
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function generate(ScheduleGeneratorService $generator): void
    {
        $this->validate();

        $this->generating = true;
        $this->progress = 0;
        $this->result = null;

        $groupIds = $this->allGroups ? [] : $this->selectedGroups;

        $generationResult = match ($this->periodType) {
            'day' => $generator->generateForDay(
                date: Carbon::parse($this->date),
                groupIds: $groupIds,
            ),
            'week' => $generator->generateForWeek(
                weekStart: Carbon::parse($this->weekStart),
                groupIds: $groupIds,
            ),
            'month' => $generator->generateForMonth(
                year: $this->year,
                month: $this->month,
                groupIds: $groupIds,
            ),
            default => throw new \InvalidArgumentException('Invalid period type.'),
        };

        $this->progress = 100;

        if ($generationResult->success) {
            $this->result = [
                'success' => true,
                'message' => $generationResult->message,
                'totalLessons' => $generationResult->totalLessons,
                'conflicts' => $generationResult->conflicts,
                'conflictDetails' => $generationResult->conflictDetails,
                'versionId' => $generationResult->version?->id,
            ];
        } else {
            $this->result = [
                'success' => false,
                'message' => $generationResult->message,
            ];
        }

        $this->generating = false;

        if ($generationResult->success) {
            session()->flash('message', __('Schedule generated successfully.'));
        } else {
            session()->flash('error', $generationResult->message);
        }
    }

    public function publish(): void
    {
        $versionId = $this->result['versionId'] ?? null;

        if ($versionId === null) {
            return;
        }

        $version = ScheduleVersion::findOrFail($versionId);
        $version->publish(auth()->id());

        $this->result['message'] = 'Расписание опубликовано. Часы учтены в нагрузке.';

        session()->flash('message', 'Расписание опубликовано. Часы учтены в нагрузке.');
    }

    public function resetForm(): void
    {
        $this->reset(
            'periodType',
            'date',
            'weekStart',
            'month',
            'year',
            'selectedGroups',
            'allGroups',
            'departmentId',
            'generating',
            'progress',
            'result',
        );

        $this->periodType = 'week';
        $this->allGroups = true;

        $now = Carbon::now();
        $this->weekStart = $now->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        $this->date = $now->format('Y-m-d');
        $this->month = (int) $now->month;
        $this->year = (int) $now->year;
    }

    public function rules(): array
    {
        $base = [
            'periodType' => 'required|in:day,week,month',
            'selectedGroups' => 'required_if:allGroups,false|array',
            'selectedGroups.*' => 'integer|exists:groups,id',
        ];

        return match ($this->periodType) {
            'day' => ['date' => 'required|date'] + $base,
            'week' => ['weekStart' => 'required|date'] + $base,
            'month' => [
                'month' => 'required|integer|between:1,12',
                'year' => 'required|integer|min:2000|max:2100',
            ] + $base,
            default => $base,
        };
    }
}
