<?php

declare(strict_types=1);

namespace App\Http\Livewire\Schedule;

use App\Models\AcademicYear;
use App\Models\CurriculumDiscipline;
use App\Models\CurriculumPractice;
use App\Models\Department;
use App\Models\Group;
use App\Models\GroupCurriculumAssignment;
use App\Models\Holiday;
use App\Models\HoursTracking;
use App\Models\Room;
use App\Models\ScheduleLesson;
use App\Models\ScheduleVersion;
use App\Models\Teacher;
use App\Models\TeacherDiscipline;
use App\Models\TeacherRoom;
use App\Models\Vacation;
use App\Services\Schedule\ConflictCheckerService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ScheduleGrid extends Component
{
    public string $viewMode = 'group';

    public int $viewId = 0;

    public string $weekStart = '';

    public ?int $versionId = null;

    public array $scheduleData = [];

    public array $practiceData = [];

    public bool $editing = false;

    public ?int $editingLessonId = null;

    #[Rule('integer|min:0')]
    public int $editTeacherId = 0;

    #[Rule('integer|min:0')]
    public int $editRoomId = 0;

    #[Rule('required|integer|min:1|max:8')]
    public int $editLessonNumber = 0;

    #[Rule('required|date')]
    public string $editDate = '';

    public string $editNotes = '';

    #[Rule('required|integer|min:1', message: 'Выберите дисциплину')]
    public int $editDisciplineId = 0;

    public ?int $editGroupId = 0;

    public bool $editIsPublished = false;

    public string $disciplineSearch = '';

    public string $teacherSearchInput = '';

    public string $roomSearch = '';

    public array $conflicts = [];

    public bool $showConflictModal = false;

    public ?int $resolvingConflictId = null;

    public string $resolutionNote = '';

    public ?string $shareLink = null;

    public bool $showShareModal = false;

    public string $shareViewMode = 'group';

    public int $shareViewId = 0;

    public function updatedViewMode(): void
    {
        $this->viewId = 0;
        $this->loadWeek();
    }

    public function updatedViewId(): void
    {
        $this->loadWeek();
    }

    public function mount(?string $version = null, ?Request $request = null): void
    {
        if ($version !== null) {
            $this->versionId = (int) $version;
        }
        if ($request !== null) {
            $qViewMode = $request->query('viewMode');
            if ($qViewMode !== null && in_array($qViewMode, ['group', 'teacher', 'room', 'department'], true)) {
                $this->viewMode = $qViewMode;
            }
            $qViewId = $request->query('viewId');
            if ($qViewId !== null) {
                $this->viewId = (int) $qViewId;
            }
            $qWeekStart = $request->query('weekStart');
            if ($qWeekStart !== null) {
                $this->weekStart = $qWeekStart;
            }
        }
        if ($this->weekStart === '') {
            if ($this->versionId !== null) {
                $version = ScheduleVersion::find($this->versionId);
                if ($version !== null && $version->date_from !== null) {
                    $this->weekStart = Carbon::parse($version->date_from)
                        ->startOfWeek(Carbon::MONDAY)
                        ->format('Y-m-d');
                } else {
                    $this->weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
                }
            } else {
                $this->weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
            }
        }
        $this->loadWeek();
    }

    public function render(): mixed
    {
        $lessonConflictMap = [];
        if (! empty($this->conflicts)) {
            $conflictLessons = collect($this->conflicts);
            foreach ($this->scheduleData as $lesson) {
                $lid = $lesson['id'] ?? 0;
                $match = $conflictLessons->first(function ($c) use ($lesson) {
                    $dateMatch = ($c['date'] ?? '') === ($lesson['date'] ?? '');
                    $numMatch = ! empty($c['lesson_number']) ? ($c['lesson_number'] == ($lesson['lesson_number'] ?? 0)) : true;
                    $teacherMatch = ! empty($c['teacher_id']) ? ($c['teacher_id'] == ($lesson['teacher_id'] ?? 0)) : true;
                    $groupMatch = ! empty($c['group_id']) ? ($c['group_id'] == ($lesson['group_id'] ?? 0)) : true;
                    $roomMatch = ! empty($c['room_id']) ? ($c['room_id'] == ($lesson['room_id'] ?? 0)) : true;

                    return $dateMatch && $numMatch && $teacherMatch && $groupMatch && $roomMatch;
                });
                if ($match) {
                    $lessonConflictMap[$lid] = $match['severity'] ?? 'warning';
                }
            }
        }

        return view('livewire.schedule.schedule-grid', [
            'lessonConflictMap' => $lessonConflictMap,
        ]);
    }

    public function loadWeek(): void
    {
        $weekStart = Carbon::parse($this->weekStart);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $lessonsQuery = ScheduleLesson::with([
            'group',
            'discipline',
            'teacher',
            'room.building',
            'lessonType',
        ])
            ->where('date', '>=', $weekStart->format('Y-m-d'))
            ->where('date', '<=', $weekEnd->format('Y-m-d'))
            ->when($this->versionId !== null, fn ($q) => $q->where('version_id', $this->versionId));

        if ($this->viewMode === 'group' && $this->viewId > 0) {
            $lessonsQuery->where('group_id', $this->viewId);
        } elseif ($this->viewMode === 'teacher' && $this->viewId > 0) {
            $lessonsQuery->where('teacher_id', $this->viewId);
        } elseif ($this->viewMode === 'room' && $this->viewId > 0) {
            $lessonsQuery->where('room_id', $this->viewId);
        } elseif ($this->viewMode === 'department' && $this->viewId > 0) {
            $lessonsQuery->whereIn('group_id', Group::where('department_id', $this->viewId)->pluck('id'));
        }

        $lessons = $lessonsQuery->orderBy('date')->orderBy('lesson_number')->get();

        $this->scheduleData = $lessons->map(function ($lesson) {
            $data = $lesson->toArray();
            $data['date'] = $lesson->date instanceof Carbon
                ? $lesson->date->format('Y-m-d')
                : $lesson->date;

            return $data;
        })->values()->toArray();

        // Load Practice data for groups
        $this->practiceData = [];
        if ($this->viewMode === 'group' || $this->viewMode === 'department') {
            $groupIds = [];
            if ($this->viewMode === 'group') {
                $groupIds = $this->viewId > 0 ? [$this->viewId] : Group::active()->pluck('id')->toArray();
            } else {
                $groupIds = $this->viewId > 0
                    ? Group::where('department_id', $this->viewId)->pluck('id')->toArray()
                    : Group::active()->pluck('id')->toArray();
            }

            $groups = Group::whereIn('id', $groupIds)->get();
            foreach ($groups as $group) {
                $assignment = $group->getCurriculumAssignmentForDate(Carbon::parse($this->weekStart));
                if ($assignment) {
                    $allPractices = CurriculumPractice::where('curriculum_plan_id', $assignment->curriculum_plan_id)
                        ->where('course_number', $group->current_course)
                        ->get();

                    foreach ($allPractices as $p) {
                        $pStart = Carbon::parse($p->start_date);
                        $pEnd = Carbon::parse($p->end_date);

                        $pYearOffset = ($pStart->month < 9) ? $pStart->year - 1 : $pStart->year;
                        $dYearOffset = ($weekStart->month < 9) ? $weekStart->year - 1 : $weekStart->year;
                        $yearDiff = $dYearOffset - $pYearOffset;

                        $normalizedStart = $pStart->copy()->addYears($yearDiff);
                        $normalizedEnd = $pEnd->copy()->addYears($yearDiff);

                        if ($normalizedStart->format('Y-m-d') <= $weekEnd->format('Y-m-d') &&
                            $normalizedEnd->format('Y-m-d') >= $weekStart->format('Y-m-d')) {

                            $workingDays = $group->getWorkingDays();
                            $pStartClamped = $normalizedStart->copy()->max($weekStart);
                            $pEndClamped = $normalizedEnd->copy()->min($weekEnd);

                            $curr = $pStartClamped->copy();
                            while ($curr->lessThanOrEqualTo($pEndClamped)) {
                                if (in_array((int) $curr->format('N'), $workingDays, true) && ! $this->isNonWorkingDay($curr)) {
                                    $this->practiceData[$group->id][] = [
                                        'date' => $curr->format('Y-m-d'),
                                        'symbol' => $p->symbol,
                                        'type' => $p->type,
                                    ];
                                }
                                $curr->addDay();
                            }
                        }
                    }
                }
            }
        }
    }

    public function previousWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)
            ->subWeek()
            ->startOfWeek(Carbon::MONDAY)
            ->format('Y-m-d');
        $this->loadWeek();
    }

    public function nextWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)
            ->addWeek()
            ->startOfWeek(Carbon::MONDAY)
            ->format('Y-m-d');
        $this->loadWeek();
    }

    private function isNonWorkingDay(Carbon $date): bool
    {
        if (Holiday::where('date', $date->toDateString())->exists()) {
            return true;
        }

        return Vacation::where('start_date', '<=', $date->toDateString())
            ->where('end_date', '>=', $date->toDateString())
            ->exists();
    }

    public function editLesson(int $lessonId): void
    {
        $lesson = ScheduleLesson::findOrFail($lessonId);
        $this->editing = true;
        $this->editingLessonId = $lesson->id;
        $this->editTeacherId = $lesson->teacher_id ?? 0;
        $this->editRoomId = $lesson->room_id ?? 0;
        $this->editLessonNumber = $lesson->lesson_number;
        $this->editDate = $lesson->date instanceof Carbon
            ? $lesson->date->format('Y-m-d')
            : $lesson->date;
        $this->editNotes = $lesson->notes ?? '';
        $this->editDisciplineId = $lesson->discipline_id ?? 0;
        $this->editGroupId = $lesson->group_id;
        $this->editIsPublished = $lesson->version?->status === 'published';

        $this->disciplineSearch = '';
        $this->teacherSearchInput = $lesson->teacher?->full_name ?? '';
        $this->roomSearch = $lesson->room ? "№{$lesson->room->number}" : '';
    }

    public function selectRoom(int $id, string $name): void
    {
        if ($id === 0) {
            $this->editRoomId = 0;
            $this->roomSearch = '';

            return;
        }
        $this->editRoomId = $id;
        $this->roomSearch = $name;
    }

    public function selectDiscipline(int $id, string $name): void
    {
        if ($id === 0) {
            $this->editDisciplineId = 0;
            $this->disciplineSearch = '';
            $this->editTeacherId = 0;
            $this->teacherSearchInput = '';

            return;
        }

        $this->editDisciplineId = $id;
        $this->disciplineSearch = $name;

        $this->editTeacherId = 0;
        $this->teacherSearchInput = '';

        $currentYear = AcademicYear::where('is_current', true)->first();
        $assignment = TeacherDiscipline::where('discipline_id', $id)
            ->where('is_primary', true)
            ->when($currentYear, fn ($q) => $q->where('academic_year_id', $currentYear->id))
            ->first();

        if ($assignment) {
            $this->editTeacherId = $assignment->teacher_id;
            $this->teacherSearchInput = $assignment->teacher->full_name;
        }
    }

    public function selectTeacher(int $id, string $name): void
    {
        if ($id === 0) {
            $this->editTeacherId = 0;
            $this->teacherSearchInput = '';

            return;
        }
        $this->editTeacherId = $id;
        $this->teacherSearchInput = $name;
    }

    #[Computed]
    public function getFilteredDisciplinesProperty(): Collection
    {
        $currentYear = AcademicYear::where('is_current', true)->first();
        if (! $this->editGroupId || $this->editGroupId <= 0) {
            return collect();
        }

        $group = Group::find($this->editGroupId);
        if (! $group) {
            return collect();
        }

        $referenceDate = $this->editDate ? Carbon::parse($this->editDate) : Carbon::parse($this->weekStart);
        $semesterNum = $group->getCurrentSemester($referenceDate);

        $planQuery = GroupCurriculumAssignment::where('group_id', $group->id)
            ->where('is_active', true);

        if ($currentYear) {
            $planId = (clone $planQuery)->where('academic_year_id', $currentYear->id)->value('curriculum_plan_id');
            if (! $planId) {
                $planId = $planQuery->value('curriculum_plan_id');
            }
        } else {
            $planId = $planQuery->value('curriculum_plan_id');
        }

        if (! $planId) {
            return collect();
        }

        $disciplines = CurriculumDiscipline::where('curriculum_plan_id', $planId)
            ->whereHas('semesters', fn ($q) => $q->where('semester_number', $semesterNum))
            ->with(['semesters' => fn ($q) => $q->where('semester_number', $semesterNum)])
            ->when($this->disciplineSearch, fn ($q) => $q->where('name', 'like', '%'.$this->disciplineSearch.'%'))
            ->orderBy('name')
            ->get();

        return $disciplines->map(function ($disc) use ($group) {
            $semester = $disc->semesters->first();
            $totalHours = $semester?->hours_total ?? 0;
            $conductedHours = HoursTracking::where('group_id', $group->id)
                ->where('discipline_id', $disc->id)
                ->where('semester_id', $semester?->id)
                ->where('is_cancelled', false)
                ->sum('hours_conducted');

            $disc->total_hours = (int) $totalHours;
            $disc->remaining_hours = (int) ($totalHours - $conductedHours);

            return $disc;
        });
    }

    #[Computed]
    public function getFilteredTeachersProperty(): Collection
    {
        $query = Teacher::where('is_active', true);
        $currentYear = AcademicYear::where('is_current', true)->first();

        if ($this->editDisciplineId > 0) {
            $assignedQuery = TeacherDiscipline::where('discipline_id', $this->editDisciplineId);

            if ($currentYear) {
                $assignedIds = (clone $assignedQuery)->where('academic_year_id', $currentYear->id)->pluck('teacher_id')->toArray();
                if (empty($assignedIds)) {
                    $assignedIds = $assignedQuery->pluck('teacher_id')->toArray();
                }
            } else {
                $assignedIds = $assignedQuery->pluck('teacher_id')->toArray();
            }

            if (! empty($assignedIds)) {
                $query->whereIn('id', $assignedIds);
            } else {
                return collect();
            }
        }

        if ($this->teacherSearchInput) {
            $query->where(function ($q) {
                $q->where('last_name', 'like', '%'.$this->teacherSearchInput.'%')
                    ->orWhere('first_name', 'like', '%'.$this->teacherSearchInput.'%');
            });
        }

        return $query->orderBy('last_name')->get();
    }

    public function saveLesson(): void
    {
        $this->validate();
        $lesson = ScheduleLesson::findOrFail($this->editingLessonId);
        $version = $lesson->version;
        $isPublished = $version && $version->status === 'published';
        $duplicate = ScheduleLesson::where('version_id', $lesson->version_id)
            ->where('group_id', $lesson->group_id)
            ->where('date', $this->editDate)
            ->where('lesson_number', $this->editLessonNumber)
            ->where('id', '!=', $lesson->id)
            ->exists();

        if ($duplicate) {
            session()->flash('error', 'В этой ячейке уже есть занятие.');

            return;
        }

        $teacherChanged = $isPublished && $this->editTeacherId > 0 && $this->editTeacherId !== $lesson->teacher_id;

        $lesson->update([
            'discipline_id' => $this->editDisciplineId,
            'lesson_type_id' => $lesson->lesson_type_id ?? 1, // Лекция по умолчанию
            'teacher_id' => $this->editTeacherId > 0 ? $this->editTeacherId : null,
            'room_id' => $this->editRoomId > 0 ? $this->editRoomId : null,
            'lesson_number' => $this->editLessonNumber,
            'notes' => $this->editNotes,
            'is_replacement' => $isPublished,
            'original_lesson_id' => $isPublished ? $lesson->id : null,
        ]);

        if ($isPublished) {
            $version->untrackLesson($lesson);
            $version->trackHours();
        }

        $this->editing = false;
        $this->editingLessonId = null;
        $this->loadWeek();
        session()->flash('message', $teacherChanged ? 'Замена сохранена. Часы пересчитаны.' : 'Занятие обновлено.');
    }

    public function deleteLesson(int $lessonId): void
    {
        ScheduleLesson::findOrFail($lessonId)->delete();
        $this->loadWeek();
        session()->flash('message', 'Занятие удалено.');
    }

    public function publish(int $versionId): void
    {
        $version = ScheduleVersion::findOrFail($versionId);
        $overlap = ScheduleVersion::where('status', 'published')
            ->where('id', '!=', $version->id)
            ->where(function ($q) use ($version) {
                $q->whereBetween('date_from', [$version->date_from, $version->date_to])
                    ->orWhereBetween('date_to', [$version->date_from, $version->date_to]);
            })->first();

        if ($overlap) {
            session()->flash('error', "ОШИБКА: На эти даты уже опубликовано расписание «{$overlap->name}».");

            return;
        }

        $version->publish(auth()->id());
        $this->loadWeek();
        session()->flash('message', 'Расписание опубликовано. Часы учтены.');
    }

    public function revertToDraft(int $versionId): void
    {
        $version = ScheduleVersion::findOrFail($versionId);
        $version->revertToDraft();
        $this->versionId = $version->id;
        $this->loadWeek();
        session()->flash('message', 'Расписание переведено в черновик. Часы аннулированы.');
    }

    public function addLesson(string $date = '', int $lessonNumber = 1, int $groupId = 0): void
    {
        $version = null;
        if ($this->versionId) {
            $version = ScheduleVersion::find($this->versionId);
            if ($version && $version->status === 'published') {
                session()->flash('error', 'Это расписание опубликовано. Сначала переведите его в черновик.');

                return;
            }
        }

        if (! $version) {
            $version = ScheduleVersion::whereIn('status', ['draft', 'generating'])->latest()->first();
        }

        if ($version === null) {
            $academicYear = AcademicYear::where('is_current', true)->first();
            $version = ScheduleVersion::create([
                'name' => 'Новое расписание',
                'academic_year_id' => $academicYear?->id,
                'date_from' => $date ?: $this->weekStart,
                'date_to' => $date ?: $this->weekStart,
                'period_type' => 'week',
                'status' => 'draft',
                'generation_type' => 'manual',
                'created_by' => auth()->id(),
            ]);
            $this->versionId = $version->id;
        }

        if ($groupId <= 0) {
            $groupId = $this->viewMode === 'group' && $this->viewId > 0 ? $this->viewId : Group::active()->first()?->id;
        }

        $group = Group::find($groupId);

        $lesson = ScheduleLesson::create([
            'version_id' => $version->id,
            'date' => $date ?: $this->weekStart,
            'lesson_number' => $lessonNumber,
            'group_id' => $groupId,
            'shift' => $group?->shift ?? 1,
            'status' => 'draft',
        ]);

        $this->editLesson($lesson->id);
    }

    public function cancelEdit(): void
    {
        if ($this->editingLessonId) {
            $lesson = ScheduleLesson::find($this->editingLessonId);
            // Если это новое занятие (дисциплина не выбрана) - удаляем при отмене
            if ($lesson && $lesson->discipline_id === null) {
                $lesson->delete();
            }
        }
        $this->editing = false;
        $this->editingLessonId = null;
        $this->loadWeek();
    }

    #[Computed]
    public function getGroupsProperty(): mixed
    {
        return Group::where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function getTeachersProperty(): mixed
    {
        return Teacher::where('is_active', true)->orderBy('last_name')->get();
    }

    #[Computed]
    public function getRoomsProperty(): mixed
    {
        return Room::with('building')->where('is_active', true)->orderBy('name')->get();
    }

    public bool $showExportModal = false;

    public function openExportModal(): void
    {
        if (! $this->versionId) {
            session()->flash('error', 'Выберите версию расписания.');

            return;
        }

        $this->showExportModal = true;
    }

    public function closeExportModal(): void
    {
        $this->showExportModal = false;
    }

    #[Computed]
    public function availableExportDates(): array
    {
        if (! $this->versionId) {
            return [];
        }

        $version = ScheduleVersion::find($this->versionId);
        if (! $version || ! $version->date_from || ! $version->date_to) {
            return [];
        }

        $dayNames = [1 => 'Пн', 2 => 'Вт', 3 => 'Ср', 4 => 'Чт', 5 => 'Пт', 6 => 'Сб', 7 => 'Вс'];
        $dates = [];
        $current = Carbon::parse($version->date_from)->startOfDay();
        $end = Carbon::parse($version->date_to)->endOfDay();

        while ($current->lessThanOrEqualTo($end)) {
            $isHoliday = Holiday::where('date', $current->format('Y-m-d'))->exists()
                || Vacation::where('start_date', '<=', $current->format('Y-m-d'))
                    ->where('end_date', '>=', $current->format('Y-m-d'))
                    ->exists();

            if (! $current->isSunday() && ! $isHoliday) {
                $hasLessons = ScheduleLesson::where('version_id', $this->versionId)
                    ->where('date', $current->format('Y-m-d'))
                    ->where('status', '!=', 'cancelled')
                    ->exists();

                $dates[] = [
                    'value' => $current->format('Y-m-d'),
                    'dayLabel' => $dayNames[$current->dayOfWeekIso] ?? '',
                    'dateLabel' => $current->format('d.m'),
                    'hasLessons' => $hasLessons,
                ];
            }

            $current->addDay();
        }

        return $dates;
    }

    /** @deprecated kept for backwards compatibility */
    public function exportExcel(): void
    {
        $this->openExportModal();
    }

    public array $highlightedLessonIds = [];

    public function highlightConflict(string $date, ?int $teacherId, ?int $groupId, ?int $roomId, ?int $lessonNumber): void
    {
        if (! $this->versionId) {
            $ver = ScheduleVersion::latest()->first();
            $this->versionId = $ver?->id;
        }
        $this->highlightedLessonIds = ScheduleLesson::where('version_id', $this->versionId)
            ->where('date', $date)
            ->when($teacherId, fn ($q) => $q->where('teacher_id', $teacherId))
            ->when($groupId, fn ($q) => $q->where('group_id', $groupId))
            ->when($roomId, fn ($q) => $q->where('room_id', $roomId))
            ->when($lessonNumber, fn ($q) => $q->where('lesson_number', $lessonNumber))
            ->pluck('id')
            ->toArray();
        $this->showConflictModal = false;
        $this->loadWeek();
        $this->dispatch('scroll-to-highlight');
    }

    public function clearHighlights(): void
    {
        $this->highlightedLessonIds = [];
        $this->dispatch('clear-highlights');
    }

    public function autoFixConflicts(ConflictCheckerService $conflictChecker): void
    {
        $version = ScheduleVersion::whereIn('status', ['draft', 'published'])->latest()->first();
        if ($version === null) {
            return;
        }
        $conflictChecker->autoFix($version->id);
        $this->conflicts = $conflictChecker->checkVersion($version->id);
        $this->loadWeek();
    }

    public function checkConflicts(ConflictCheckerService $conflictChecker): void
    {
        $version = ScheduleVersion::whereIn('status', ['draft', 'published'])->latest()->first();
        if ($version === null) {
            return;
        }
        $this->conflicts = $conflictChecker->checkVersion($version->id);
        $this->showConflictModal = true;
    }

    public function checkConflictsForWeek(ConflictCheckerService $conflictChecker): void
    {
        $version = ScheduleVersion::whereIn('status', ['draft', 'published'])->latest()->first();
        if ($version === null) {
            return;
        }
        $weekStart = Carbon::parse($this->weekStart);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);
        $this->conflicts = $conflictChecker->checkVersionForRange($version->id, $weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d'));
        $this->showConflictModal = true;
    }

    public function startResolve(int $conflictId): void
    {
        $this->resolvingConflictId = $conflictId;
        $this->resolutionNote = '';
    }

    public function resolveConflict(ConflictCheckerService $conflictChecker): void
    {
        if ($this->resolvingConflictId === null) {
            return;
        }
        $conflictChecker->resolveConflict($this->resolvingConflictId, auth()->id(), $this->resolutionNote);
        $this->resolvingConflictId = null;
        $version = ScheduleVersion::whereIn('status', ['draft', 'published'])->latest()->first();
        if ($version) {
            $this->conflicts = $conflictChecker->checkVersion($version->id);
        }
    }

    public function openShareModal(): void
    {
        $this->shareViewMode = $this->viewMode;
        $this->shareViewId = $this->viewId > 0 ? $this->viewId : (Department::first()?->id ?? 1);
        $this->shareLink = null;
        $this->showShareModal = true;
    }

    public string $shareDate = '';

    public string $shareType = 'week';

    public function generateShareLink(): void
    {
        $versionId = $this->versionId ?? ScheduleVersion::latest()->value('id');
        if ($this->shareType === 'day') {
            $this->shareLink = route('schedule.day', ['department' => $this->shareViewId, 'date' => $this->shareDate ?: now()->format('Y-m-d'), 'version' => $versionId]);
        } else {
            $this->shareLink = route('schedule.shared', ['version' => $versionId, 'viewMode' => $this->shareViewMode, 'viewId' => $this->shareViewId]);
        }
        $this->showShareModal = false;
    }

    #[Computed]
    public function getFilteredRoomsProperty(): Collection
    {
        $query = Room::with('building')->where('is_active', true);

        if ($this->roomSearch && ! Room::where('id', $this->editRoomId)->where('number', str_replace('№', '', $this->roomSearch))->exists()) {
            $search = str_replace(['№', ' '], '', $this->roomSearch);
            $query->where('number', 'like', '%'.$search.'%');
        }

        $rooms = $query->orderBy('number')->get();

        $teacherPreferences = collect();
        if ($this->editTeacherId > 0) {
            $teacherPreferences = TeacherRoom::where('teacher_id', $this->editTeacherId)
                ->get()
                ->keyBy('room_id');
        }

        return $rooms->map(function ($room) use ($teacherPreferences) {
            $pref = $teacherPreferences->get($room->id);
            if ($pref) {
                if ($pref->priority === 1) {
                    $room->suitability = 'perfect';
                    $room->suitability_label = 'Закреплена (главная)';
                } else {
                    $room->suitability = 'preferred';
                    $room->suitability_label = 'Предпочтительно';
                }
            } else {
                $room->suitability = 'neutral';
                $room->suitability_label = 'Доступна';
            }
            $room->display_name = "№{$room->number}".($room->building ? ' · '.($room->building->short_name ?? $room->building->name) : '');

            return $room;
        })->sortByDesc(fn ($r) => $r->suitability === 'perfect' ? 2 : ($r->suitability === 'preferred' ? 1 : 0));
    }

    #[Computed]
    public function getDepartmentsProperty(): mixed
    {
        return Department::active()->orderBy('name')->get();
    }
}
