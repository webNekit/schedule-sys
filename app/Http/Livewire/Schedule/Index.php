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
        $version->lessons()->delete();
        $version->delete();

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
