<?php

declare(strict_types=1);

namespace App\Http\Livewire\Teachers;

use App\Models\AcademicYear;
use App\Models\Building;
use App\Models\CurriculumDiscipline;
use App\Models\CurriculumPlan;
use App\Models\CurriculumSemester;
use App\Models\Department;
use App\Models\Group;
use App\Models\GroupCurriculumAssignment;
use App\Models\HoursTracking;
use App\Models\Room;
use App\Models\ScheduleVersion;
use App\Models\Teacher;
use App\Models\TeacherBuilding;
use App\Models\TeacherDiscipline;
use App\Models\TeacherDisciplineSemester;
use App\Models\TeacherPosition;
use App\Models\TeacherRoom;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Show extends Component
{
    public Teacher $teacher;

    public string $activeTab = 'info';

    public bool $showTeacherForm = false;

    public string $teacherLastName = '';

    public string $teacherFirstName = '';

    public string $teacherMiddleName = '';

    public ?int $teacherDepartmentId = null;

    public ?int $teacherPositionId = null;

    public string $teacherEmploymentType = 'full_time';

    public string $teacherRate = '1.00';

    public string $teacherEmail = '';

    public string $teacherPhone = '';

    public array $teacherWorkingDays = [];

    public array $teacherWorkingLessonNumbers = [];

    public bool $showDisciplineForm = false;

    public ?int $disciplinePlanId = null;

    public ?int $disciplineId = null;

    public ?int $disciplineGroupId = null;

    public ?int $disciplineAcademicYearId = null;

    public int $disciplinePlannedHours = 0;

    public bool $showBuildingForm = false;

    public ?int $selectedBuildingId = null;

    public ?int $selectedRoomId = null;

    public int $roomPriority = 1;

    public function mount(Teacher $teacher): void
    {
        $this->teacher = $teacher->load([
            'department.faculty',
            'position',
            'buildings.building',
            'rooms.room.building',
        ]);
    }

    public function openTeacherForm(): void
    {
        $this->teacherLastName = $this->teacher->last_name;
        $this->teacherFirstName = $this->teacher->first_name;
        $this->teacherMiddleName = $this->teacher->middle_name ?? '';
        $this->teacherDepartmentId = $this->teacher->department_id;
        $this->teacherPositionId = $this->teacher->position_id;
        $this->teacherEmploymentType = $this->teacher->employment_type ?? 'full_time';
        $this->teacherRate = (string) ($this->teacher->rate ?? 1.00);
        $this->teacherEmail = $this->teacher->email ?? '';
        $this->teacherPhone = $this->teacher->phone ?? '';
        $this->teacherWorkingDays = $this->teacher->working_days ?? [];
        $this->teacherWorkingLessonNumbers = $this->teacher->working_lesson_numbers ?? [];
        $this->showTeacherForm = true;
    }

    public function saveTeacher(): void
    {
        $this->validate([
            'teacherLastName' => 'required|string|max:255',
            'teacherFirstName' => 'required|string|max:255',
            'teacherMiddleName' => 'nullable|string|max:255',
            'teacherDepartmentId' => 'nullable|integer|exists:departments,id',
            'teacherPositionId' => 'nullable|integer|exists:teacher_positions,id',
            'teacherEmploymentType' => 'nullable|in:full_time,part_time,hourly',
            'teacherRate' => 'required|numeric|min:0|max:3',
        ]);

        $this->teacher->update([
            'last_name' => $this->teacherLastName,
            'first_name' => $this->teacherFirstName,
            'middle_name' => $this->teacherMiddleName ?: null,
            'department_id' => $this->teacherDepartmentId,
            'position_id' => $this->teacherPositionId,
            'employment_type' => $this->teacherEmploymentType,
            'rate' => (float) $this->teacherRate,
            'email' => $this->teacherEmail ?: null,
            'phone' => $this->teacherPhone ?: null,
            'working_days' => $this->teacherWorkingDays,
            'working_lesson_numbers' => $this->teacherWorkingLessonNumbers,
        ]);

        $this->showTeacherForm = false;
        $this->teacher->refresh();
    }

    public function openDisciplineForm(): void
    {
        $this->disciplinePlanId = null;
        $this->disciplineId = null;
        $this->disciplineGroupId = null;
        $this->disciplineAcademicYearId = null;
        $this->disciplinePlannedHours = 0;
        $this->showDisciplineForm = true;
    }

    public function saveDiscipline(): void
    {
        $this->validate([
            'disciplineId' => 'required|integer|exists:curriculum_disciplines,id',
            'disciplineGroupId' => 'nullable|integer|exists:groups,id',
            'disciplineAcademicYearId' => 'nullable|integer|exists:academic_years,id',
            'disciplinePlannedHours' => 'required|integer|min:0',
        ]);

        $td = TeacherDiscipline::create([
            'teacher_id' => $this->teacher->id,
            'discipline_id' => $this->disciplineId,
            'group_id' => $this->disciplineGroupId,
            'academic_year_id' => $this->disciplineAcademicYearId,
            'planned_hours' => $this->disciplinePlannedHours,
            'is_primary' => true,
        ]);

        $semesters = CurriculumSemester::where('discipline_id', $this->disciplineId)->get();
        foreach ($semesters as $semester) {
            TeacherDisciplineSemester::create([
                'teacher_discipline_id' => $td->id,
                'curriculum_semester_id' => $semester->id,
                'planned_hours' => $semester->hours_total,
            ]);
        }

        $this->showDisciplineForm = false;
    }

    public function updateSemesterHours(int $semesterId, int $hours): void
    {
        TeacherDisciplineSemester::where('id', $semesterId)
            ->whereHas('teacherDiscipline', fn ($q) => $q->where('teacher_id', $this->teacher->id))
            ->update(['planned_hours' => $hours]);
    }

    public function openBuildingForm(): void
    {
        $this->selectedBuildingId = null;
        $this->selectedRoomId = null;
        $this->roomPriority = 1;
        $this->showBuildingForm = true;
    }

    public function assignBuilding(): void
    {
        $this->validate([
            'selectedBuildingId' => 'required|integer|exists:buildings,id',
        ]);

        $exists = TeacherBuilding::where('teacher_id', $this->teacher->id)
            ->where('building_id', $this->selectedBuildingId)
            ->exists();

        if (! $exists) {
            TeacherBuilding::create([
                'teacher_id' => $this->teacher->id,
                'building_id' => $this->selectedBuildingId,
                'is_primary' => $this->teacher->buildings()->count() === 0,
            ]);
        }

        $this->selectedBuildingId = null;
        $this->teacher->load('buildings.building');
    }

    public function removeBuilding(int $buildingId): void
    {
        TeacherBuilding::where('teacher_id', $this->teacher->id)
            ->where('building_id', $buildingId)
            ->delete();

        TeacherRoom::where('teacher_id', $this->teacher->id)
            ->whereIn('room_id', Room::where('building_id', $buildingId)->pluck('id'))
            ->delete();

        $this->teacher->load(['buildings.building', 'rooms.room']);
    }

    public function selectRoom(int $roomId): void
    {
        $this->selectedRoomId = $this->selectedRoomId === $roomId ? null : $roomId;
    }

    public function assignRoom(): void
    {
        $this->validate([
            'selectedBuildingId' => 'required|integer|exists:buildings,id',
            'selectedRoomId' => 'required|integer|exists:rooms,id',
            'roomPriority' => 'required|integer|min:1|max:5',
        ]);

        $exists = TeacherRoom::where('teacher_id', $this->teacher->id)
            ->where('room_id', $this->selectedRoomId)
            ->exists();

        if (! $exists) {
            TeacherRoom::create([
                'teacher_id' => $this->teacher->id,
                'room_id' => $this->selectedRoomId,
                'priority' => $this->roomPriority,
                'is_personal' => false,
            ]);
        }

        $this->selectedRoomId = null;
        $this->roomPriority = 1;
        $this->teacher->load(['buildings.building', 'rooms.room']);
    }

    public function removeRoom(int $roomId): void
    {
        TeacherRoom::where('teacher_id', $this->teacher->id)
            ->where('room_id', $roomId)
            ->delete();

        $this->teacher->load(['buildings.building', 'rooms.room']);
    }

    public function toggleWorkingDay(int $day, bool $checked): void
    {
        if ($checked) {
            $this->teacherWorkingDays[] = $day;
            $this->teacherWorkingDays = array_unique($this->teacherWorkingDays);
        } else {
            $this->teacherWorkingDays = array_values(array_filter($this->teacherWorkingDays, fn ($d) => (int) $d !== $day));
        }
        sort($this->teacherWorkingDays);
    }

    public function toggleWorkingLessonNumber(int $num, bool $checked): void
    {
        if ($checked) {
            $this->teacherWorkingLessonNumbers[] = $num;
            $this->teacherWorkingLessonNumbers = array_unique($this->teacherWorkingLessonNumbers);
        } else {
            $this->teacherWorkingLessonNumbers = array_values(array_filter($this->teacherWorkingLessonNumbers, fn ($n) => (int) $n !== $num));
        }
        sort($this->teacherWorkingLessonNumbers);
    }

    public function toggleWorkingLessonNumberForDay(int $day, int $num, bool $checked): void
    {
        $current = $this->teacherWorkingLessonNumbers;
        if (! is_array($current)) {
            $current = [];
        }

        $daySlots = $current[$day] ?? [];

        if ($checked) {
            $daySlots[] = $num;
            $daySlots = array_unique($daySlots);
        } else {
            $daySlots = array_values(array_filter($daySlots, fn ($v) => (int) $v !== $num));
        }

        sort($daySlots);
        $current[$day] = $daySlots;
        $this->teacherWorkingLessonNumbers = $current;
    }

    public function removeDiscipline(int $disciplineId): void
    {
        TeacherDiscipline::where('teacher_id', $this->teacher->id)
            ->where('id', $disciplineId)
            ->delete();
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $currentAcademicYear = AcademicYear::where('is_current', true)->first();

        // 1. Получаем ПЛАНОВУЮ нагрузку
        $plannedWorkloads = TeacherDiscipline::where('teacher_id', $this->teacher->id)
            ->with([
                'discipline.curriculumPlan',
                'group',
                'semesters.curriculumSemester',
                'academicYear',
            ])
            ->get();

        // 2. Получаем ФАКТИЧЕСКУЮ нагрузку из трекинга (чтобы учесть физкультуру и замены)
        $conductedHours = HoursTracking::where('teacher_id', $this->teacher->id)
            ->where('is_cancelled', false)
            ->with(['discipline.curriculumPlan', 'group', 'semester'])
            ->get()
            ->groupBy(fn($h) => $h->group_id . '-' . $h->discipline_id);

        // 3. Собираем все уникальные комбинации Группа + Дисциплина
        $workloads = collect();

        // Сначала добавляем все плановые
        foreach ($plannedWorkloads as $pw) {
            $key = ($pw->group_id ?? '') . '-' . $pw->discipline_id;
            
            if (! $pw->group && $pw->discipline?->curriculumPlan) {
                $assignment = GroupCurriculumAssignment::where('curriculum_plan_id', $pw->discipline->curriculumPlan->id)
                    ->with('group')
                    ->first();
                $pw->resolvedGroup = $assignment?->group;
            } else {
                $pw->resolvedGroup = $pw->group;
            }
            
            $workloads->put($key, $pw);
        }

        // Затем добавляем те, по которым были фактические часы, но нет записи в TeacherDiscipline (например, физкультура)
        foreach ($conductedHours as $key => $hours) {
            if (! $workloads->has($key)) {
                $first = $hours->first();
                $virtualTd = new TeacherDiscipline([
                    'teacher_id' => $this->teacher->id,
                    'group_id' => $first->group_id,
                    'discipline_id' => $first->discipline_id,
                ]);
                $virtualTd->setRelation('discipline', $first->discipline);
                $virtualTd->setRelation('group', $first->group);
                $virtualTd->resolvedGroup = $first->group;
                
                // Создаем виртуальные семестры на основе трекинга
                // Пытаемся подтянуть плановые часы из учебного плана, если это основная нагрузка
                $semesters = $hours->groupBy('semester_id')->map(function($hGroup) {
                    $firstH = $hGroup->first();
                    $tds = new TeacherDisciplineSemester([
                        'curriculum_semester_id' => $firstH->semester_id,
                        'planned_hours' => $firstH->semester?->hours_total ?? 0, // Показываем план дисциплины как базу
                    ]);
                    $tds->setRelation('curriculumSemester', $firstH->semester);
                    return $tds;
                });
                $virtualTd->setRelation('semesters', $semesters);
                
                $workloads->put($key, $virtualTd);
            }
        }

        $workloads = $workloads->values();

        $hasPublishedSchedule = ScheduleVersion::where('status', 'published')->exists();

        $allSemesterIds = $workloads
            ->flatMap(fn ($td) => $td->semesters->pluck('curriculum_semester_id'))
            ->filter()
            ->unique();

        $conductedMap = collect();
        if ($hasPublishedSchedule && $allSemesterIds->isNotEmpty()) {
            $conductedMap = HoursTracking::where('teacher_id', $this->teacher->id)
                ->whereIn('semester_id', $allSemesterIds)
                ->where('is_cancelled', false)
                ->selectRaw('COALESCE(SUM(hours_conducted), 0) as total, discipline_id, group_id, semester_id')
                ->groupBy('discipline_id', 'group_id', 'semester_id')
                ->get()
                ->keyBy(fn ($item) => $item->discipline_id.'-'.($item->group_id ?? '').'-'.$item->semester_id);
        }

        $disciplines = TeacherDiscipline::where('teacher_id', $this->teacher->id)
            ->with(['discipline.curriculumPlan', 'discipline.semesters.controlForm', 'semesters.curriculumSemester', 'group', 'subgroup', 'academicYear'])
            ->get()
            ->groupBy(fn ($td) => $td->discipline?->name ?? 'Без дисциплины');

        $groupIds = TeacherDiscipline::where('teacher_id', $this->teacher->id)
            ->whereNotNull('group_id')
            ->pluck('group_id')
            ->unique();

        $groups = Group::whereIn('id', $groupIds)
            ->with('specialty')
            ->get();

        $disciplinesByPlan = collect();
        if ($this->disciplinePlanId) {
            $disciplinesByPlan = CurriculumDiscipline::where('curriculum_plan_id', $this->disciplinePlanId)
                ->orderBy('name')
                ->get();
        }

        $roomsByBuilding = collect();
        if ($this->selectedBuildingId) {
            $roomsByBuilding = Room::where('building_id', $this->selectedBuildingId)
                ->active()
                ->orderBy('floor')
                ->orderBy('number')
                ->get()
                ->groupBy('floor');
        }

        $totalHoursSem1 = 0;
        $totalHoursSem2 = 0;
        foreach ($workloads as $td) {
            $totalHoursSem1 += $td->semesters
                ->where('curriculumSemester.semester_in_course', 1)
                ->sum('planned_hours');
            $totalHoursSem2 += $td->semesters
                ->where('curriculumSemester.semester_in_course', 2)
                ->sum('planned_hours');
        }
        $totalHoursGrand = $totalHoursSem1 + $totalHoursSem2;

        return view('livewire.teachers.show', [
            'disciplines' => $disciplines,
            'groups' => $groups,
            'workloads' => $workloads,
            'hasPublishedSchedule' => $hasPublishedSchedule,
            'conductedMap' => $conductedMap,
            'totalHoursSem1' => $totalHoursSem1,
            'totalHoursSem2' => $totalHoursSem2,
            'totalHoursGrand' => $totalHoursGrand,
            'currentAcademicYear' => $currentAcademicYear,
            'departments' => Department::active()->orderBy('name')->get(),
            'positions' => TeacherPosition::orderBy('name')->get(),
            'curriculumPlans' => CurriculumPlan::with('specialty')->orderBy('name')->get(),
            'disciplinesByPlan' => $disciplinesByPlan,
            'allGroups' => Group::active()->orderBy('name')->get(),
            'academicYears' => AcademicYear::orderBy('year_start', 'desc')->get(),
            'buildings' => Building::active()->orderBy('name')->get(),
            'roomsByBuilding' => $roomsByBuilding,
            'allBuildings' => Building::active()->with('rooms')->orderBy('name')->get(),
        ]);
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
    }
}
