<?php

declare(strict_types=1);

namespace App\Http\Livewire\Schedule;

use App\Models\AcademicYear;
use App\Models\ScheduleVersion;
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

    public function updatedSelectAll(bool $value): void
    {
        if ($value) {
            $this->selectedVersions = ScheduleVersion::query()
                ->when($this->search, fn($q) => $q->where('name', 'like', '%'.$this->search.'%'))
                ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
                ->pluck('id')
                ->map(fn($id) => (string)$id)
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

    public function export(int $id): void
    {
        $version = ScheduleVersion::findOrFail($id);

        session()->flash('message', 'Экспорт: '.$version->name);
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

        $this->selectedVersions = array_diff($this->selectedVersions, [(string)$id, $id]);

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
