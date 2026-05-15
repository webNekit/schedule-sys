<?php

declare(strict_types=1);

namespace App\Http\Livewire\Teachers;

use App\Models\Department;
use App\Models\Teacher;
use App\Models\TeacherPosition;
use App\Services\Import\ExcelDictionaryImportService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination, WithFileUploads;

    public string $search = '';
    public string $departmentFilter = '';
    public string $statusFilter = '';

    public bool $showCreateModal = false;
    public string $newLastName = '';
    public string $newFirstName = '';
    public string $newMiddleName = '';
    public ?int $newDepartmentId = null;
    public ?int $newPositionId = null;
    public string $newEmploymentType = 'full_time';
    public string $newRate = '1.00';
    public string $newEmail = '';
    public string $newPhone = '';
    public bool $newIsActive = true;

    // Импорт
    public bool $showImportModal = false;
    public $importFile;

    public function openCreateModal(): void
    {
        $this->resetNewForm();
        $this->showCreateModal = true;
    }

    public function resetNewForm(): void
    {
        $this->newLastName = '';
        $this->newFirstName = '';
        $this->newMiddleName = '';
        $this->newDepartmentId = null;
        $this->newPositionId = null;
        $this->newEmploymentType = 'full_time';
        $this->newRate = '1.00';
        $this->newEmail = '';
        $this->newPhone = '';
        $this->newIsActive = true;
    }

    public function saveTeacher(): void
    {
        $this->validate([
            'newLastName' => 'required|string|max:255',
            'newFirstName' => 'required|string|max:255',
            'newMiddleName' => 'nullable|string|max:255',
            'newDepartmentId' => 'nullable|integer|exists:departments,id',
            'newPositionId' => 'nullable|integer|exists:teacher_positions,id',
            'newEmploymentType' => 'required|in:full_time,part_time,hourly',
            'newRate' => 'required|numeric|min:0|max:3',
            'newEmail' => 'nullable|email|max:255',
            'newPhone' => 'nullable|string|max:50',
        ]);

        Teacher::create([
            'last_name' => $this->newLastName,
            'first_name' => $this->newFirstName,
            'middle_name' => $this->newMiddleName ?: null,
            'full_name' => trim("{$this->newLastName} {$this->newFirstName} {$this->newMiddleName}"),
            'short_name' => $this->newLastName . ' ' . mb_substr($this->newFirstName, 0, 1) . '.' . ($this->newMiddleName ? mb_substr($this->newMiddleName, 0, 1) . '.' : ''),
            'department_id' => $this->newDepartmentId,
            'position_id' => $this->newPositionId,
            'employment_type' => $this->newEmploymentType,
            'rate' => (float) $this->newRate,
            'email' => $this->newEmail ?: null,
            'phone' => $this->newPhone ?: null,
            'is_active' => $this->newIsActive,
        ]);

        $this->showCreateModal = false;
        $this->resetNewForm();
        session()->flash('message', 'Преподаватель успешно добавлен.');
    }

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
            $result = $importService->importTeachers($fullPath);
            $this->closeImportModal();

            if ($result['imported'] > 0) {
                session()->flash('message', "Импорт завершен. Добавлено/обновлено: {$result['imported']}.");
            }
            if (!empty($result['errors'])) {
                session()->flash('error', "Ошибки импорта: " . implode(' ', array_slice($result['errors'], 0, 3)) . (count($result['errors']) > 3 ? '...' : ''));
            }
        } catch (\Exception $e) {
            session()->flash('error', "Ошибка при импорте: " . $e->getMessage());
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

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->departmentFilter = '';
        $this->statusFilter = '';
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $query = Teacher::query()
            ->with(['department', 'position']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('last_name', 'like', '%' . $this->search . '%')
                    ->orWhere('first_name', 'like', '%' . $this->search . '%')
                    ->orWhere('middle_name', 'like', '%' . $this->search . '%')
                    ->orWhere('full_name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->departmentFilter) {
            $query->where('department_id', $this->departmentFilter);
        }

        if ($this->statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($this->statusFilter === 'inactive') {
            $query->where('is_active', false);
        }

        return view('livewire.teachers.index', [
            'teachers' => $query->latest()->paginate(15),
            'departments' => Department::where('is_active', true)->get(),
            'positions' => TeacherPosition::orderBy('name')->get(),
        ]);
    }
}