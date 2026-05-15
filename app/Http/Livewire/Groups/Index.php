<?php

declare(strict_types=1);

namespace App\Http\Livewire\Groups;

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\Group;
use App\Models\Specialty;
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
    public string $courseFilter = '';
    public string $statusFilter = '';

    public bool $showCreateModal = false;
    public string $newName = '';
    public string $newShortName = '';
    public ?int $newSpecialtyId = null;
    public ?int $newDepartmentId = null;
    public ?int $newAcademicYearId = null;
    public int $newCourse = 1;
    public int $newStudentsCount = 0;
    public int $newShift = 1;
    public string $newEnrollmentDate = '';
    public string $newStatus = 'active';

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
        $this->newName = '';
        $this->newShortName = '';
        $this->newSpecialtyId = null;
        $this->newDepartmentId = null;
        $this->newAcademicYearId = null;
        $this->newCourse = 1;
        $this->newStudentsCount = 0;
        $this->newShift = 1;
        $this->newEnrollmentDate = '';
        $this->newStatus = 'active';
    }

    public function saveGroup(): void
    {
        $this->validate([
            'newName' => 'required|string|max:255',
            'newShortName' => 'nullable|string|max:50',
            'newSpecialtyId' => 'nullable|integer|exists:specialties,id',
            'newDepartmentId' => 'nullable|integer|exists:departments,id',
            'newAcademicYearId' => 'nullable|integer|exists:academic_years,id',
            'newCourse' => 'required|integer|min:1|max:6',
            'newStudentsCount' => 'required|integer|min:0',
            'newShift' => 'required|integer|in:1,2',
            'newEnrollmentDate' => 'nullable|date',
            'newStatus' => 'required|in:active,graduated,academic_leave',
        ]);

        Group::create([
            'name' => $this->newName,
            'short_name' => $this->newShortName ?: null,
            'specialty_id' => $this->newSpecialtyId,
            'department_id' => $this->newDepartmentId,
            'academic_year_id' => $this->newAcademicYearId,
            'current_course' => $this->newCourse,
            'students_count' => $this->newStudentsCount,
            'shift' => $this->newShift,
            'enrollment_date' => $this->newEnrollmentDate ?: null,
            'status' => $this->newStatus,
            'is_active' => true,
        ]);

        $this->showCreateModal = false;
        $this->resetNewForm();
        session()->flash('message', 'Группа успешно создана.');
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
            $result = $importService->importGroups($fullPath);
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

    public function updatedCourseFilter(): void
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
        $this->courseFilter = '';
        $this->statusFilter = '';
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $query = Group::query()
            ->with(['specialty', 'department', 'academicYear']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('short_name', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->departmentFilter) {
            $query->where('department_id', $this->departmentFilter);
        }

        if ($this->courseFilter) {
            $query->where('current_course', $this->courseFilter);
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        return view('livewire.groups.index', [
            'groups' => $query->latest()->paginate(15),
            'departments' => Department::where('is_active', true)->get(),
            'specialties' => Specialty::where('is_active', true)->orderBy('name')->get(),
            'academicYears' => AcademicYear::orderBy('year_start', 'desc')->get(),
            'courses' => range(1, 4),
            'statuses' => ['active' => 'Активна', 'graduated' => 'Выпущена', 'academic_leave' => 'Академ. отпуск'],
        ]);
    }
}