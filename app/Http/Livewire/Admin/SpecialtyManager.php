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

    public ?string $studyYears9 = null;

    public ?string $studyYears11 = null;

    public string $baseEducation = '';

    public string $formOfStudy = '';

    public ?int $budgetPlaces = null;

    public ?int $commercialPlaces = null;

    public bool $isActive = true;

    public string $search = '';

    public string $departmentFilter = '';

    // Импорт
    public bool $showImportModal = false;

    public $importFile;

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

        // Восстанавливаем значение в виде строки "3,9"
        $this->studyYears9 = $spec->study_years_9 ?? ($spec->study_months > 0 ? "{$spec->study_years},{$spec->study_months}" : (string) $spec->study_years);
        $this->studyYears11 = $spec->study_years_11 ? (string) $spec->study_years_11 : null;

        $this->baseEducation = $spec->base_education ?? '';
        $this->formOfStudy = $spec->form_of_study ?? '';
        $this->budgetPlaces = $spec->budget_places;
        $this->commercialPlaces = $spec->commercial_places;
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
            'studyYears9' => 'nullable|string|max:10',
            'studyYears11' => 'nullable|string|max:10',
            'baseEducation' => 'nullable|string|max:50',
            'formOfStudy' => 'nullable|string|max:50',
            'budgetPlaces' => 'nullable|integer|min:0',
            'commercialPlaces' => 'nullable|integer|min:0',
            'isActive' => 'boolean',
        ]);

        // Нормализуем ввод (запятую меняем на точку для базы)
        $val9 = $this->studyYears9 ? str_replace(',', '.', trim($this->studyYears9)) : null;
        $val11 = $this->studyYears11 ? str_replace(',', '.', trim($this->studyYears11)) : null;

        $data = [
            'code' => $this->code,
            'name' => $this->name,
            'short_name' => $this->shortName ?: null,
            'qualification' => $this->qualification ?: null,
            'department_id' => $this->departmentId,
            'education_level_id' => $this->educationLevelId,
            'study_years_9' => $val9,
            'study_years_11' => $val11,
            'base_education' => $this->baseEducation ?: null,
            'form_of_study' => $this->formOfStudy ?: null,
            'budget_places' => $this->budgetPlaces,
            'commercial_places' => $this->commercialPlaces,
            'is_active' => $this->isActive,
        ];

        // Раскладываем срок 9 классов на годы и месяцы только если значение задано
        if ($val9 !== null && str_contains($val9, '.')) {
            $parts = explode('.', $val9);
            $data['study_years'] = (int) ($parts[0] ?? 0);
            $data['study_months'] = (int) ($parts[1] ?? 0);
        } elseif ($val9 !== null) {
            $data['study_years'] = (int) $val9;
            $data['study_months'] = 0;
        } else {
            $data['study_years'] = null;
            $data['study_months'] = null;
        }

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

    private function resetForm(): void
    {
        $this->code = '';
        $this->name = '';
        $this->shortName = '';
        $this->qualification = '';
        $this->departmentId = null;
        $this->educationLevelId = null;
        $this->studyYears9 = null;
        $this->studyYears11 = null;
        $this->baseEducation = '';
        $this->formOfStudy = '';
        $this->budgetPlaces = null;
        $this->commercialPlaces = null;
        $this->isActive = true;
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
