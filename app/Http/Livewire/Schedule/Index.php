<?php

declare(strict_types=1);

namespace App\Http\Livewire\Schedule;

use App\Models\AcademicYear;
use App\Models\Holiday;
use App\Models\ScheduleLesson;
use App\Models\ScheduleVersion;
use App\Models\Vacation;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public array $selectedVersions = [];

    public bool $selectAll = false;

    public bool $showExportModal = false;

    public ?int $exportVersionId = null;

    public function updatedSelectAll(bool $value): void
    {
        if ($value) {
            $this->selectedVersions = ScheduleVersion::query()
                ->when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->toArray();
        } else {
            $this->selectedVersions = [];
        }
    }

    public function updatedSelectedVersions(): void
    {
        $this->selectAll = false;
    }

    public function deleteSelected(): void
    {
        if (empty($this->selectedVersions)) {
            return;
        }

        $versions = ScheduleVersion::whereIn('id', $this->selectedVersions)->get();

        foreach ($versions as $version) {
            foreach ($version->lessons as $lesson) {
                $lesson->delete();
            }
            $version->delete();
        }

        $count = count($this->selectedVersions);
        $this->selectedVersions = [];
        $this->selectAll = false;

        session()->flash('message', "Удалено версий расписания: {$count}");
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->statusFilter = '';
    }

    public function create(): void
    {
        $academicYear = AcademicYear::where('is_current', true)->first();

        $now = now();

        $version = ScheduleVersion::create([
            'name' => 'Новое расписание',
            'academic_year_id' => $academicYear?->id,
            'date_from' => $now,
            'date_to' => $now,
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);

        $this->redirect(route('schedule.view', $version));
    }

    public function publish(int $id): void
    {
        $version = ScheduleVersion::findOrFail($id);
        $version->publish(auth()->id());

        session()->flash('message', 'Расписание опубликовано. Часы учтены в нагрузке.');
    }

    public function openExportModal(int $id): void
    {
        ScheduleVersion::findOrFail($id);
        $this->exportVersionId = $id;
        $this->showExportModal = true;
    }

    public function closeExportModal(): void
    {
        $this->showExportModal = false;
        $this->exportVersionId = null;
    }

    #[Computed]
    public function exportVersion(): ?ScheduleVersion
    {
        return $this->exportVersionId ? ScheduleVersion::find($this->exportVersionId) : null;
    }

    #[Computed]
    public function availableExportDates(): array
    {
        if (! $this->exportVersionId) {
            return [];
        }

        $version = ScheduleVersion::find($this->exportVersionId);
        if (! $version || ! $version->date_from || ! $version->date_to) {
            return [];
        }

        $dates = [];
        $current = Carbon::parse($version->date_from)->startOfDay();
        $end = Carbon::parse($version->date_to)->endOfDay();

        while ($current->lessThanOrEqualTo($end)) {
            // Пропускаем воскресенья, праздники и каникулы
            $isHoliday = Holiday::where('date', $current->format('Y-m-d'))->exists()
                || Vacation::where('start_date', '<=', $current->format('Y-m-d'))
                    ->where('end_date', '>=', $current->format('Y-m-d'))
                    ->exists();

            if (! $current->isSunday() && ! $isHoliday) {
                $hasLessons = ScheduleLesson::where('version_id', $this->exportVersionId)
                    ->where('date', $current->format('Y-m-d'))
                    ->where('status', '!=', 'cancelled')
                    ->exists();

                $dayNames = [1 => 'Пн', 2 => 'Вт', 3 => 'Ср', 4 => 'Чт', 5 => 'Пт', 6 => 'Сб', 7 => 'Вс'];
                $dates[] = [
                    'value' => $current->format('Y-m-d'),
                    'dayLabel' => $dayNames[$current->dayOfWeekIso] ?? '',
                    'dateLabel' => $current->format('d.m'),
                    'hasLessons' => $hasLessons,
                ];
            }

            $current->addDay();
        }

        return $dates;
    }

    /** @deprecated Use openExportModal instead */
    public function export(int $id): void
    {
        $this->openExportModal($id);
    }

    public function archive(int $id): void
    {
        $version = ScheduleVersion::findOrFail($id);
        $version->update(['status' => 'archived']);

        session()->flash('message', 'Расписание отправлено в архив.');
    }

    public function deleteVersion(int $id): void
    {
        $version = ScheduleVersion::findOrFail($id);
        foreach ($version->lessons as $lesson) {
            $lesson->delete();
        }
        $version->delete();

        $this->selectedVersions = array_diff($this->selectedVersions, [(string) $id, $id]);

        session()->flash('message', 'Расписание удалено.');
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $query = ScheduleVersion::query()
            ->with(['academicYear', 'department', 'createdBy']);

        if ($this->search) {
            $query->where('name', 'like', '%'.$this->search.'%');
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        return view('livewire.schedule.index', [
            'versions' => $query->latest()->paginate(15),
        ]);
    }
}
