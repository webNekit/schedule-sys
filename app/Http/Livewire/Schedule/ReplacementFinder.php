<?php

declare(strict_types=1);

namespace App\Http\Livewire\Schedule;

use App\Models\AcademicYear;
use App\Models\Building;
use App\Models\CurriculumDiscipline;
use App\Models\Group;
use App\Models\GroupCurriculumAssignment;
use App\Models\HoursTracking;
use App\Models\Room;
use App\Models\ScheduleLesson;
use App\Models\ScheduleVersion;
use App\Models\Teacher;
use App\Models\TeacherDiscipline;
use App\Services\Schedule\ConflictCheckerService;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ReplacementFinder extends Component
{
    public string $date;

    public ?int $lessonNumber = null;

    public ?int $groupId = null;

    public ?int $disciplineId = null;

    public ?int $buildingId = null;

    public string $groupSearch = '';

    public string $disciplineSearch = '';

    public string $buildingSearch = '';

    public function mount(): void
    {
        $this->date = Carbon::now()->toDateString();
    }

    public function selectGroup(int $id, string $name): void
    {
        $this->groupId = $id > 0 ? $id : null;
        $this->groupSearch = $id > 0 ? $name : '';
        $this->buildingId = null;
        $this->buildingSearch = '';
        $this->lessonNumber = null;
        $this->disciplineId = null;
        $this->disciplineSearch = '';
    }

    public function selectBuilding(int $id, string $name): void
    {
        $this->buildingId = $id > 0 ? $id : null;
        $this->buildingSearch = $name ?: 'Все корпуса';
    }

    public function selectLesson(int $number): void
    {
        $this->lessonNumber = (int) $number;
    }

    public function selectDiscipline(int $id, string $name): void
    {
        $this->disciplineId = $id > 0 ? $id : null;
        $this->disciplineSearch = $id > 0 ? $name : '';
    }

    #[Computed]
    public function isSelectionComplete(): bool
    {
        return ! empty($this->groupId) && ! empty($this->lessonNumber) && ! empty($this->disciplineId);
    }

    #[Computed]
    public function filteredGroups()
    {
        return Group::where('is_active', true)
            ->when($this->groupSearch, fn ($q) => $q->where('name', 'like', '%'.$this->groupSearch.'%'))
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function filteredDisciplines()
    {
        if (! $this->groupId) {
            return collect();
        }

        $group = Group::find($this->groupId);
        if (! $group) {
            return collect();
        }

        $currentYear = AcademicYear::where('is_current', true)->first();
        $semesterNum = $group->getCurrentSemester(Carbon::parse($this->date));

        $planQuery = GroupCurriculumAssignment::where('group_id', $group->id)->where('is_active', true);
        $planId = $currentYear
            ? ($planQuery->clone()->where('academic_year_id', $currentYear->id)->value('curriculum_plan_id') ?? $planQuery->value('curriculum_plan_id'))
            : $planQuery->value('curriculum_plan_id');

        if (! $planId) {
            return collect();
        }

        return CurriculumDiscipline::where('curriculum_plan_id', $planId)
            ->whereHas('semesters', fn ($q) => $q->where('semester_number', $semesterNum))
            ->with(['semesters' => fn ($q) => $q->where('semester_number', $semesterNum)])
            ->when($this->disciplineSearch, fn ($q) => $q->where('name', 'like', '%'.$this->disciplineSearch.'%'))
            ->get()
            ->groupBy(fn ($d) => trim(($d->code ?? '').' '.$d->name))
            ->map(function ($groupItems) use ($group, $currentYear) {
                $first = $groupItems->first();
                $totalHours = 0;
                $conductedHours = 0;

                foreach ($groupItems as $disc) {
                    $semester = $disc->semesters->first();
                    if (! $semester) {
                        continue;
                    }
                    $totalHours += (int) ($semester->hours_total ?? 0);
                    $conductedHours += (int) HoursTracking::where('group_id', $group->id)
                        ->where('discipline_id', $disc->id)
                        ->where('semester_id', $semester->id)
                        ->where('is_cancelled', false)
                        ->where('counts_for_group', true)
                        ->sum('hours_conducted');
                }

                $primaryTeacherQuery = TeacherDiscipline::where('discipline_id', $first->id)
                    ->where('is_primary', true)
                    ->when($currentYear, fn ($q) => $q->where('academic_year_id', $currentYear->id));

                $primaryTeacher = (clone $primaryTeacherQuery)->where('group_id', $group->id)->first()?->teacher?->full_name
                    ?? $primaryTeacherQuery->whereNull('group_id')->first()?->teacher?->full_name;

                return (object) [
                    'id' => $first->id,
                    'name' => $first->name,
                    'code' => $first->code,
                    'total_hours' => $totalHours,
                    'remaining_hours' => $totalHours - $conductedHours,
                    'primary_teacher' => $primaryTeacher,
                ];
            })
            ->sortBy('name')
            ->values();
    }

    #[Computed]
    public function filteredBuildings()
    {
        return Building::when($this->buildingSearch, fn ($q) => $q->where('name', 'like', '%'.$this->buildingSearch.'%'))
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function availableTeachers()
    {
        $conflictChecker = app(ConflictCheckerService::class);
        $currentYear = AcademicYear::where('is_current', true)->first();

        $teachers = Teacher::with(['position', 'rooms.room', 'disciplines'])->where('is_active', true)
            ->when($this->buildingId, function ($q) {
                $q->whereHas('rooms.room', fn ($r) => $r->where('building_id', $this->buildingId));
            })
            ->when($this->disciplineId, function ($q) use ($currentYear) {
                // ВАРИАНТ Б: Жесткий фильтр по предмету
                $targetDiscipline = CurriculumDiscipline::find($this->disciplineId);
                if ($targetDiscipline) {
                    $q->whereHas('disciplines', function ($d) use ($targetDiscipline, $currentYear) {
                        $d->where(function ($sq) use ($targetDiscipline) {
                            $sq->where('discipline_id', $this->disciplineId)
                                ->orWhereHas('discipline', fn ($dq) => $dq->where('name', $targetDiscipline->name));
                        })
                            ->where('planned_hours', '>', 0)
                            ->when($currentYear, fn ($cy) => $cy->where('academic_year_id', $currentYear->id));
                    });
                }
            })
            ->get();

        return $teachers->map(function ($teacher) use ($currentYear) {
            $reasons = [];
            $isAvailable = true;
            $targetLesson = $this->lessonNumber ?: 1;

            if (! $teacher->isAvailableOn($this->date, $targetLesson)) {
                $isAvailable = false;
                $reasons[] = 'Не рабочий день/часы по графику';
            }

            if ($this->lessonNumber) {
                $hasConflict = ScheduleLesson::where('teacher_id', $teacher->id)
                    ->whereDate('date', $this->date)
                    ->where('lesson_number', (int) $this->lessonNumber)
                    ->whereIn('status', ['published', 'draft'])
                    ->exists();

                if ($hasConflict) {
                    $isAvailable = false;
                    $reasons[] = 'Уже ведет другую пару в это время';
                }
            }

            $workloadInfo = null;
            if ($this->disciplineId) {
                $assignment = $teacher->disciplines()
                    ->where('discipline_id', $this->disciplineId)
                    ->when($currentYear, fn ($q) => $q->where('academic_year_id', $currentYear->id))
                    ->first();

                if (! $assignment) {
                    $targetDiscipline = CurriculumDiscipline::find($this->disciplineId);
                    if ($targetDiscipline) {
                        $assignment = $teacher->disciplines()
                            ->whereHas('discipline', fn ($q) => $q->where('name', $targetDiscipline->name))
                            ->when($currentYear, fn ($q) => $q->where('academic_year_id', $currentYear->id))
                            ->first();
                    }
                }

                if ($assignment) {
                    $total = (int) ($assignment->planned_hours ?? 0);

                    // Реальная вычитка из таблицы трекинга, в рамках текущего
                    // учебного года — иначе план одного семестра сравнивался бы
                    // с фактом за все годы и остаток был бы заниженным.
                    $actual = (int) HoursTracking::where('teacher_id', $teacher->id)
                        ->where('discipline_id', $assignment->discipline_id)
                        ->where('is_cancelled', false)
                        ->when($currentYear, fn ($q) => $q->where('academic_year_id', $currentYear->id))
                        ->sum('hours_conducted');

                    $workloadInfo = [
                        'total' => $total,
                        'remaining' => $total - $actual,
                    ];
                }
            }

            $teacher->is_replacement_available = $isAvailable;
            $teacher->availability_reasons = $reasons;
            $teacher->workload_info = $workloadInfo;

            $teacher->busy_slots = ScheduleLesson::where('teacher_id', $teacher->id)
                ->whereDate('date', $this->date)
                ->whereIn('status', ['published', 'draft'])
                ->pluck('lesson_number')
                ->unique()
                ->sort()
                ->values()
                ->toArray();

            $prefTeacherRoom = $teacher->rooms->where('priority', 1)->first() ?? $teacher->rooms->first();
            $teacher->preferred_room = $prefTeacherRoom?->room;

            return $teacher;
        })->sort(function ($a, $b) {
            if ($a->is_replacement_available != $b->is_replacement_available) {
                return $b->is_replacement_available <=> $a->is_replacement_available;
            }

            return $a->last_name <=> $b->last_name;
        });
    }

    public function assignReplacement(int $teacherId, ?int $roomId): void
    {
        if (! $this->groupId || ! $this->disciplineId || ! $this->lessonNumber) {
            session()->flash('error', 'Сначала выберите группу, пару и предмет.');

            return;
        }

        $group = Group::find($this->groupId);
        $carbonDate = Carbon::parse($this->date);

        // 1. Ищем последнюю версию, где уже есть уроки для этой группы
        $version = ScheduleVersion::whereDate('date_from', '<=', $this->date)
            ->whereDate('date_to', '>=', $this->date)
            ->whereHas('lessons', fn ($q) => $q->where('group_id', $this->groupId))
            ->latest()
            ->first();

        // 2. Если не нашли, ищем любой черновик
        if (! $version) {
            $version = ScheduleVersion::where('status', 'draft')
                ->whereDate('date_from', '<=', $this->date)
                ->whereDate('date_to', '>=', $this->date)
                ->latest()
                ->first();
        }

        // 3. Если совсем ничего - создаем новую
        if (! $version) {
            $academicYear = AcademicYear::where('is_current', true)->first();
            $version = ScheduleVersion::create([
                'name' => 'Замена: '.$carbonDate->translatedFormat('d.m.Y'),
                'academic_year_id' => $academicYear?->id,
                'date_from' => $carbonDate->copy()->startOfWeek(),
                'date_to' => $carbonDate->copy()->endOfWeek(),
                'status' => 'draft',
                'generation_type' => 'manual',
                'created_by' => auth()->id(),
            ]);
        }

        // Оригинальную пару в ЭТОЙ версии НЕ удаляем, а помечаем отменённой —
        // так сохраняется история замены, а обсервер вернёт часы прежнего
        // преподавателя (он пару не отвёл). Берём первый оригинал для связи.
        $original = ScheduleLesson::where('version_id', $version->id)
            ->whereDate('date', $this->date)
            ->where('lesson_number', $this->lessonNumber)
            ->where('group_id', $this->groupId)
            ->where('status', '!=', 'cancelled')
            ->orderBy('id')
            ->get();

        $originalLessonId = $original->first()?->id;
        $lessonTypeId = $original->first()?->lesson_type_id ?? 1;

        foreach ($original as $lesson) {
            $lesson->update(['status' => 'cancelled']);
        }

        // Создание нового урока вызовет created()-обсервер, который начислит
        // часы заменяющему преподавателю в опубликованной версии.
        ScheduleLesson::create([
            'version_id' => $version->id,
            'date' => $this->date,
            'lesson_number' => $this->lessonNumber,
            'group_id' => $this->groupId,
            'discipline_id' => $this->disciplineId,
            'teacher_id' => $teacherId,
            'room_id' => $roomId,
            'building_id' => $this->buildingId ?: ($roomId ? Room::find($roomId)?->building_id : null),
            'shift' => $group->shift,
            'is_replacement' => true,
            'original_lesson_id' => $originalLessonId,
            'lesson_type_id' => $lessonTypeId,
            'status' => $version->status === 'published' ? 'published' : 'draft',
            'created_by' => auth()->id(),
        ]);

        session()->flash('message', 'Замена успешно назначена.');
        $this->redirect(route('schedule.view', ['version' => $version->id, 'viewMode' => 'group', 'viewId' => $this->groupId]));
    }

    #[Computed]
    public function groupBusySlots(): array
    {
        if (! $this->groupId) {
            return [];
        }

        return ScheduleLesson::where('group_id', $this->groupId)
            ->whereDate('date', $this->date)
            ->whereIn('status', ['published', 'draft'])
            ->pluck('lesson_number')
            ->unique()
            ->sort()
            ->values()
            ->toArray();
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.schedule.replacement-finder');
    }
}
