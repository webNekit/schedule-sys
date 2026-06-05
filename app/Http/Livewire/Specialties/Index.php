<?php

declare(strict_types=1);

namespace App\Http\Livewire\Specialties;

use App\Models\Department;
use App\Models\Specialty;
use App\Services\Import\ExcelDictionaryImportService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Index extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';

    public string $departmentFilter = '';

    // Модалка создания
    public bool $showCreateModal = false;

    public string $newCode = '';

    public string $newName = '';

    public string $newShortName = '';

    public ?int $newDepartmentId = null;

    public ?int $newEducationLevelId = null;

    public int $newMaxCourses = 4;

    // Импорт
    public bool $showImportModal = false;

    public $importFile;

    public function openImportModal(): void
    {
        $this->importFile = null;
        $this->showImportModal = true;
    }

    public function closeImportModal(): void
    {
        $this->showImportModal = false;
        $this->importFile = null;
    }

    public function importExcel(ExcelDictionaryImportService $importService): void
    {
        $this->validate([
            'importFile' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $storedPath = $this->importFile->store('imports', 'local');
        $fullPath = Storage::disk('local')->path($storedPath);

        try {
            $result = $importService->importSpecialties($fullPath);
            $this->closeImportModal();

            if ($result['imported'] > 0) {
                session()->flash('message', "Импорт завершен. Добавлено/обновлено: {$result['imported']}.");
            }
            if (! empty($result['errors'])) {
                session()->flash('error', 'Ошибки импорта: '.implode(' ', array_slice($result['errors'], 0, 3)).(count($result['errors']) > 3 ? '...' : ''));
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Ошибка при импорте: '.$e->getMessage());
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedDepartmentFilter(): void
    {
        $this->resetPage();
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $query = Specialty::query()
            ->with(['department', 'educationLevel']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('short_name', 'like', '%'.$this->search.'%')
                    ->orWhere('code', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->departmentFilter) {
            $query->where('department_id', $this->departmentFilter);
        }

        return view('livewire.specialties.index', [
            'specialties' => $query->latest()->paginate(15),
            'departments' => Department::where('is_active', true)->get(),
        ]);
    }
}
