<?php

declare(strict_types=1);

namespace App\Http\Livewire\Curriculum;

use App\Models\AcademicYear;
use App\Models\Holiday;
use App\Models\Vacation;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;

#[Layout('components.layouts.app')]
class AcademicPeriodsManager extends Component
{
    public ?int $editingYearId = null;

    public ?int $editingVacationId = null;

    #[Rule('required|string|max:255')]
    public string $name = '';

    #[Rule('required|integer|min:2000|max:2100')]
    public int $yearStart;

    #[Rule('required|integer|min:2000|max:2100')]
    public int $yearEnd;

    #[Rule('required|date')]
    public string $dateStart = '';

    #[Rule('required|date')]
    public string $dateEnd = '';

    #[Rule('required|date')]
    public string $firstSemesterStart = '';

    #[Rule('required|date')]
    public string $firstSemesterEnd = '';

    #[Rule('required|date')]
    public string $secondSemesterStart = '';

    #[Rule('required|date')]
    public string $secondSemesterEnd = '';

    #[Rule('boolean')]
    public bool $isCurrent = false;

    #[Rule('required|string|max:255')]
    public string $vacationName = '';

    #[Rule('required|date')]
    public string $vacationStartDate = '';

    #[Rule('required|date')]
    public string $vacationEndDate = '';

    #[Rule('required|integer|min:0')]
    public int $vacationDurationDays = 0;

    public ?int $vacationYearId = null;

    public function mount(): void
    {
        $this->yearStart = now()->year;
        $this->yearEnd = now()->year + 1;
    }

    public function render(): mixed
    {
        return view('livewire.curriculum.academic-periods-manager', [
            'academicYears' => AcademicYear::with('vacations')
                ->orderBy('year_start', 'desc')
                ->get(),
        ]);
    }

    public function saveYear(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'yearStart' => 'required|integer|min:2000|max:2100',
            'yearEnd' => 'required|integer|min:2000|max:2100',
            'dateStart' => 'required|date',
            'dateEnd' => 'required|date',
            'firstSemesterStart' => 'required|date',
            'firstSemesterEnd' => 'required|date',
            'secondSemesterStart' => 'required|date',
            'secondSemesterEnd' => 'required|date',
            'isCurrent' => 'boolean',
        ]);

        if ($this->isCurrent) {
            AcademicYear::where('is_current', true)->update(['is_current' => false]);
        }

        $data = [
            'name' => $this->name,
            'year_start' => $this->yearStart,
            'year_end' => $this->yearEnd,
            'date_start' => $this->dateStart,
            'date_end' => $this->dateEnd,
            'first_semester_start' => $this->firstSemesterStart,
            'first_semester_end' => $this->firstSemesterEnd,
            'second_semester_start' => $this->secondSemesterStart,
            'second_semester_end' => $this->secondSemesterEnd,
            'is_current' => $this->isCurrent,
        ];

        if ($this->editingYearId) {
            AcademicYear::findOrFail($this->editingYearId)->update($data);
            session()->flash('message', __('Academic year updated successfully.'));
        } else {
            AcademicYear::create($data);
            session()->flash('message', __('Academic year created successfully.'));
        }

        $this->resetYearForm();
    }

    public function editYear(int $id): void
    {
        $year = AcademicYear::findOrFail($id);

        $this->editingYearId = $year->id;
        $this->name = $year->name;
        $this->yearStart = $year->year_start;
        $this->yearEnd = $year->year_end;
        $this->dateStart = $year->date_start->format('Y-m-d');
        $this->dateEnd = $year->date_end->format('Y-m-d');
        $this->firstSemesterStart = $year->first_semester_start->format('Y-m-d');
        $this->firstSemesterEnd = $year->first_semester_end->format('Y-m-d');
        $this->secondSemesterStart = $year->second_semester_start->format('Y-m-d');
        $this->secondSemesterEnd = $year->second_semester_end->format('Y-m-d');
        $this->isCurrent = $year->is_current;
    }

    public function deleteYear(int $id): void
    {
        $year = AcademicYear::withCount('vacations')->findOrFail($id);

        if ($year->vacations_count > 0) {
            session()->flash('error', __('Cannot delete academic year with existing vacations.'));

            return;
        }

        $year->delete();

        session()->flash('message', __('Academic year deleted successfully.'));
    }

    public function addVacation(int $academicYearId): void
    {
        $this->validate([
            'vacationName' => 'required|string|max:255',
            'vacationStartDate' => 'required|date',
            'vacationEndDate' => 'required|date|after_or_equal:vacationStartDate',
        ]);

        Vacation::create([
            'academic_year_id' => $academicYearId,
            'name' => $this->vacationName,
            'start_date' => $this->vacationStartDate,
            'end_date' => $this->vacationEndDate,
            'duration_days' => $this->vacationDurationDays,
        ]);

        $this->resetVacationForm();

        session()->flash('message', __('Vacation added successfully.'));
    }

    public function editVacation(int $id): void
    {
        $vacation = Vacation::findOrFail($id);

        $this->editingVacationId = $vacation->id;
        $this->vacationYearId = $vacation->academic_year_id;
        $this->vacationName = $vacation->name;
        $this->vacationStartDate = $vacation->start_date->format('Y-m-d');
        $this->vacationEndDate = $vacation->end_date->format('Y-m-d');
        $this->vacationDurationDays = $vacation->duration_days;
    }

    public function updateVacation(): void
    {
        if ($this->editingVacationId === null || $this->vacationYearId === null) {
            return;
        }

        $this->validate([
            'vacationName' => 'required|string|max:255',
            'vacationStartDate' => 'required|date',
            'vacationEndDate' => 'required|date|after_or_equal:vacationStartDate',
        ]);

        Vacation::findOrFail($this->editingVacationId)->update([
            'name' => $this->vacationName,
            'start_date' => $this->vacationStartDate,
            'end_date' => $this->vacationEndDate,
            'duration_days' => $this->vacationDurationDays,
        ]);

        $this->resetVacationForm();

        session()->flash('message', __('Vacation updated successfully.'));
    }

    public function deleteVacation(int $id): void
    {
        Vacation::findOrFail($id)->delete();

        session()->flash('message', __('Vacation deleted successfully.'));
    }

    public function updatedVacationEndDate(): void
    {
        $this->recalculateVacationDuration();
    }

    public function updatedVacationStartDate(): void
    {
        $this->recalculateVacationDuration();
    }

    private function recalculateVacationDuration(): void
    {
        if ($this->vacationStartDate !== '' && $this->vacationEndDate !== '') {
            $this->vacationDurationDays = (int) Carbon::parse($this->vacationStartDate)
                ->diffInDays(Carbon::parse($this->vacationEndDate));
        }
    }

    public function resetYearForm(): void
    {
        $this->editingYearId = null;
        $this->name = '';
        $this->yearStart = now()->year;
        $this->yearEnd = now()->year + 1;
        $this->dateStart = '';
        $this->dateEnd = '';
        $this->firstSemesterStart = '';
        $this->firstSemesterEnd = '';
        $this->secondSemesterStart = '';
        $this->secondSemesterEnd = '';
        $this->isCurrent = false;
    }

    public function resetVacationForm(): void
    {
        $this->editingVacationId = null;
        $this->vacationYearId = null;
        $this->vacationName = '';
        $this->vacationStartDate = '';
        $this->vacationEndDate = '';
        $this->vacationDurationDays = 0;
    }

    public function importHolidays(): void
    {
        $year = (int) now()->year;

        $holidays = $this->getRussianHolidays($year);

        $imported = 0;
        $skipped = 0;

        foreach ($holidays as $holiday) {
            Holiday::firstOrCreate(
                ['date' => $holiday['date'], 'year' => $year],
                [
                    'date' => $holiday['date'],
                    'name' => $holiday['name'],
                    'type' => 'public',
                    'year' => $year,
                    'description' => $holiday['description'] ?? null,
                ],
            )->wasRecentlyCreated ? $imported++ : $skipped++;
        }

        session()->flash('message', "Импортировано праздников на {$year} год: {$imported} добавлено, {$skipped} пропущено.");
    }

    private function getRussianHolidays(int $year): array
    {
        $holidays = [
            ['date' => "{$year}-01-01", 'name' => 'Новый год', 'description' => 'Новогодние каникулы'],
            ['date' => "{$year}-01-02", 'name' => 'Новый год', 'description' => 'Новогодние каникулы'],
            ['date' => "{$year}-01-03", 'name' => 'Новый год', 'description' => 'Новогодние каникулы'],
            ['date' => "{$year}-01-04", 'name' => 'Новый год', 'description' => 'Новогодние каникулы'],
            ['date' => "{$year}-01-05", 'name' => 'Новый год', 'description' => 'Новогодние каникулы'],
            ['date' => "{$year}-01-06", 'name' => 'Новый год', 'description' => 'Новогодние каникулы'],
            ['date' => "{$year}-01-07", 'name' => 'Рождество Христово', 'description' => 'Рождество Христово'],
            ['date' => "{$year}-01-08", 'name' => 'Новый год', 'description' => 'Новогодние каникулы'],
            ['date' => "{$year}-02-23", 'name' => 'День защитника Отечества', 'description' => 'День защитника Отечества'],
            ['date' => "{$year}-03-08", 'name' => 'Международный женский день', 'description' => 'Международный женский день'],
            ['date' => "{$year}-05-01", 'name' => 'Праздник Весны и Труда', 'description' => 'Праздник Весны и Труда'],
            ['date' => "{$year}-05-09", 'name' => 'День Победы', 'description' => 'День Победы'],
            ['date' => "{$year}-06-12", 'name' => 'День России', 'description' => 'День России'],
            ['date' => "{$year}-11-04", 'name' => 'День народного единства', 'description' => 'День народного единства'],
        ];

        $this->addWeekendTransfers($holidays, $year);

        usort($holidays, fn (array $a, array $b) => $a['date'] <=> $b['date']);

        return $holidays;
    }

    private function addWeekendTransfers(array &$holidays, int $year): void
    {
        $dateMap = [];

        foreach ($holidays as $h) {
            $dateMap[$h['date']] = true;
        }

        foreach ($holidays as $h) {
            $date = Carbon::parse($h['date']);
            $dayOfWeek = (int) $date->format('N');

            if ($dayOfWeek === 6 || $dayOfWeek === 7) {
                $nextMonday = $date->copy()->next(Carbon::MONDAY)->toDateString();

                if (! isset($dateMap[$nextMonday])) {
                    $holidays[] = [
                        'date' => $nextMonday,
                        'name' => 'Выходной (перенос)',
                        'description' => "Перенос с {$h['date']} ({$h['name']})",
                    ];

                    $dateMap[$nextMonday] = true;
                }
            }
        }
    }

    public function cancelEdit(): void
    {
        $this->resetYearForm();
        $this->resetVacationForm();
    }
}
