<?php

declare(strict_types=1);

namespace App\Http\Livewire\Curriculum;

use App\Models\AcademicYear;
use App\Models\Specialty;
use App\Services\Curriculum\CurriculumImportService;
use App\Services\Curriculum\ExcelCurriculumParserService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class CurriculumImportForm extends Component
{
    use WithFileUploads;

    #[Rule('required|file|mimes:xlsx,xls,xml|max:10240')]
    public ?UploadedFile $xmlFile = null;

    #[Rule('required|exists:specialties,id')]
    public int $specialtyId = 0;

    #[Rule('required|exists:academic_years,id')]
    public int $academicYearId = 0;

    public array $previewData = [];

    public bool $showPreview = false;

    public bool $importing = false;

    public int $progress = 0;

    public function mount(): void
    {
        $specialtyId = request()->integer('specialty');

        if ($specialtyId > 0 && Specialty::whereKey($specialtyId)->exists()) {
            $this->specialtyId = $specialtyId;
        }

        $this->academicYearId = AcademicYear::where('is_current', true)->value('id')
            ?? AcademicYear::orderByDesc('year_start')->value('id')
            ?? 0;
    }

    public function render(): mixed
    {
        return view('livewire.curriculum.curriculum-import-form', [
            'specialties' => Specialty::where('is_active', true)
                ->orderBy('name')
                ->get(),
            'academicYears' => AcademicYear::orderBy('year_start', 'desc')
                ->get(),
        ]);
    }

    public function updatedXmlFile(): void
    {
        $this->validateOnly('xmlFile');
        $this->showPreview = false;
        $this->previewData = [];
    }

    public function preview(CurriculumImportService $importService): void
    {
        $this->validate();

        $path = $this->xmlFile->getRealPath();

        $parsed = $importService->preview($path);

        $this->previewData = [
            'specialty' => $parsed->specialty,
            'disciplines' => $parsed->disciplines,
            'semesters' => $parsed->semesters,
            'errors' => $parsed->errors,
            'warnings' => $parsed->warnings,
            'disciplineCount' => $parsed->getDisciplineCount(),
            'semesterCount' => $parsed->getSemesterCount(),
        ];

        $this->showPreview = $parsed->errors === [];
    }

    public function import(CurriculumImportService $importService, ExcelCurriculumParserService $excelParser): void
    {
        $this->validate([
            'xmlFile' => 'required|file|mimes:xlsx,xls,xml|max:10240',
            'specialtyId' => 'required|integer|exists:specialties,id',
            'academicYearId' => 'required|integer|exists:academic_years,id',
        ]);

        $this->importing = true;
        $this->progress = 0;

        $ext = strtolower($this->xmlFile->getClientOriginalExtension());

        // 1. Сохраняем файл в постоянное хранилище
        $storedPath = $this->xmlFile->store('curriculum_uploads', 'local');

        // 2. Получаем правильный абсолютный путь через фасад Storage
        $fullPath = Storage::disk('local')->path($storedPath);

        try {
            if (in_array($ext, ['xlsx', 'xls'])) {
                // Excel import
                $parsed = $excelParser->parseFile($fullPath);
                $result = $excelParser->importToDatabase(
                    $parsed,
                    (int) $this->specialtyId,
                    (int) $this->academicYearId,
                    auth()->id(),
                    $storedPath
                );

                $this->progress = 100;

                if ($result['imported'] > 0) {
                    session()->flash('message', __('Импорт завершён: :imported дисциплин добавлено.', [
                        'imported' => $result['imported'],
                    ]));
                } else {
                    session()->flash('error', __('Импорт не удался: никаких дисциплин не добавлено.'));
                }

                if (! empty($result['errors'])) {
                    session()->flash('warning', __('Ошибки при импорте: :errors', [
                        'errors' => implode(', ', $result['errors']),
                    ]));
                }
            } else {
                // XML - existing logic
                $result = $importService->import(
                    xmlPath: $fullPath,
                    specialtyId: $this->specialtyId,
                    academicYearId: $this->academicYearId,
                    userId: auth()->id(),
                );

                $this->progress = 100;

                if ($result->success) {
                    session()->flash('message', __(
                        'Учебный план импортирован. :disciplines дисциплин и :semesters семестров добавлено.',
                        [
                            'disciplines' => $result->disciplinesImported,
                            'semesters' => $result->semestersImported,
                        ],
                    ));

                    $this->resetForm();
                } else {
                    session()->flash('error', __('Ошибка импорта: :error', [
                        'error' => implode(', ', $result->errors),
                    ]));
                }
            }
        } catch (\Throwable $e) {
            session()->flash('error', 'Ошибка импорта: '.$e->getMessage());
            Log::error('Ошибка импорта учебного плана', ['error' => $e->getMessage()]);
        }

        $this->importing = false;
    }

    public function resetForm(): void
    {
        $this->reset([
            'xmlFile',
            'specialtyId',
            'academicYearId',
            'previewData',
            'showPreview',
            'importing',
            'progress',
        ]);
    }
}
