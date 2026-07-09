<?php

declare(strict_types=1);

namespace App\Http\Livewire\Admin;

use App\Models\Department;
use App\Models\EducationLevel;
use App\Models\Specialty;
use App\Services\Import\ExcelDictionaryImportService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class SpecialtyManager extends Component
{
    use WithFileUploads, WithPagination;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $code = '';

    public string $name = '';

    public string $shortName = '';

    public string $qualification = '';

    public ?int $departmentId = null;

    public ?int $educationLevelId = null;

    public string $studyYears9 = '';

    public string $studyYears11 = '';

    public string $baseEducation = '';

    public string $formOfStudy = '';

    public ?int $budgetPlaces = null;

    public ?int $contractPlaces = null;

    public bool $isActive = true;

    public string $search = '';

    public string $departmentFilter = '';

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

    public function create(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $spec = Specialty::findOrFail($id);
        $this->editingId = $spec->id;
        $this->code = $spec->code;
        $this->name = $spec->name;
        $this->shortName = $spec->short_name ?? '';
        $this->qualification = $spec->qualification ?? '';
        $this->departmentId = $spec->department_id;
        $this->educationLevelId = $spec->education_level_id;
        $this->studyYears9 = (string) ($spec->study_years_9 ?? $spec->study_years ?? '');
        $this->studyYears11 = (string) ($spec->study_years_11 ?? '');
        $this->baseEducation = $spec->base_education ?? '';
        $this->formOfStudy = $spec->form_of_study ?? '';
        $this->budgetPlaces = $spec->budget_places;
        $this->contractPlaces = $spec->contract_places;
        $this->isActive = $spec->is_active;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'shortName' => 'nullable|string|max:100',
            'departmentId' => 'nullable|integer|exists:departments,id',
            'educationLevelId' => 'nullable|integer|exists:education_levels,id',
            'studyYears9' => ['nullable', 'string', 'regex:/^\d{1,2}([.,]([0-9]|1[0-1]))?$/'],
            'studyYears11' => ['nullable', 'string', 'regex:/^\d{1,2}([.,]([0-9]|1[0-1]))?$/'],
            'baseEducation' => 'nullable|string|max:50',
            'formOfStudy' => 'nullable|string|max:50',
            'budgetPlaces' => 'nullable|integer|min:0',
            'contractPlaces' => 'nullable|integer|min:0',
            'isActive' => 'boolean',
        ], [
            'studyYears9.regex' => 'Укажите срок в формате «годы» или «годы,месяцы», например 2 или 2,9.',
            'studyYears11.regex' => 'Укажите срок в формате «годы» или «годы,месяцы», например 3 или 3,10.',
        ]);

        $years9 = $this->normalizeStudyDuration($this->studyYears9);
        $years11 = $this->normalizeStudyDuration($this->studyYears11);

        $data = [
            'code' => $this->code,
            'name' => $this->name,
            'short_name' => $this->shortName ?: null,
            'qualification' => $this->qualification ?: null,
            'department_id' => $this->departmentId,
            'education_level_id' => $this->educationLevelId,
            'study_years' => $years9 !== null ? (int) $years9 : 4,
            'study_years_9' => $years9,
            'study_years_11' => $years11,
            'base_education' => $this->baseEducation ?: null,
            'form_of_study' => $this->formOfStudy ?: null,
            'budget_places' => $this->budgetPlaces,
            'contract_places' => $this->contractPlaces,
            'is_active' => $this->isActive,
        ];

        if ($this->editingId) {
            Specialty::findOrFail($this->editingId)->update($data);
            session()->flash('message', 'Специальность обновлена');
        } else {
            Specialty::create($data);
            session()->flash('message', 'Специальность создана');
        }

        $this->showForm = false;
        $this->resetForm();
    }

    public function delete(int $id): void
    {
        Specialty::findOrFail($id)->delete();
        session()->flash('message', 'Специальность удалена');
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    /**
     * Приводит срок обучения к виду "годы,месяцы" (запятая как разделитель).
     * Возвращает null для пустой строки.
     */
    private function normalizeStudyDuration(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return str_replace('.', ',', $value);
    }

    private function resetForm(): void
    {
        $this->code = '';
        $this->name = '';
        $this->shortName = '';
        $this->qualification = '';
        $this->departmentId = null;
        $this->educationLevelId = null;
        $this->studyYears9 = '';
        $this->studyYears11 = '';
        $this->baseEducation = '';
        $this->formOfStudy = '';
        $this->budgetPlaces = null;
        $this->contractPlaces = null;
        $this->isActive = true;
    }

    public function render(): mixed
    {
        $query = Specialty::with('department');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('code', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->departmentFilter) {
            $query->where('department_id', $this->departmentFilter);
        }

        return view('livewire.admin.specialty-manager', [
            'specialties' => $query->orderBy('name')->paginate(15),
            'departments' => Department::active()->orderBy('name')->get(),
            'educationLevels' => EducationLevel::orderBy('name')->get(),
        ]);
    }
}
