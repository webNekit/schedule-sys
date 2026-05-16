<?php

declare(strict_types=1);

namespace App\Http\Livewire\Schedule;

use App\Models\AcademicYear;
use App\Models\CurriculumDiscipline;
use App\Models\Department;
use App\Models\Group;
use App\Models\Room;
use App\Models\ScheduleLesson;
use App\Models\ScheduleVersion;
use App\Models\Teacher;
use App\Models\TeacherRoom;
use App\Services\Export\ExcelExportService;
use App\Services\Schedule\ConflictCheckerService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

#[Layout('components.layouts.app')]
class ScheduleGrid extends Component
{
    public string $viewMode = 'group';

    public int $viewId = 0;

    public string $weekStart = '';

    public ?int $versionId = null;

    public array $scheduleData = [];

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
        $lessons = ScheduleLesson::with([
            'group',
            'discipline',
            'teacher',
            'room.building',
            'lessonType',
        ])
            ->where('date', '>=', $weekStart->format('Y-m-d'))
            ->where('date', '<=', $weekEnd->format('Y-m-d'))
            ->when($this->versionId !== null, fn ($q) => $q->where('version_id', $this->versionId))
            ->when(
                $this->viewMode === 'group' && $this->viewId > 0,
                fn ($q) => $q->where('group_id', $this->viewId),
            )
            ->when(
                $this->viewMode === 'teacher' && $this->viewId > 0,
                fn ($q) => $q->where('teacher_id', $this->viewId),
            )
            ->when(
                $this->viewMode === 'room' && $this->viewId > 0,
                fn ($q) => $q->where('room_id', $this->viewId),
            )
            ->when(
                $this->viewMode === 'department' && $this->viewId > 0,
                fn ($q) => $q->whereIn('group_id', Group::where('department_id', $this->viewId)->pluck('id')),
            )
            ->orderBy('date')
            ->orderBy('lesson_number')
            ->get();
        $this->scheduleData = $lessons->map(function ($lesson) {
            $data = $lesson->toArray();
            $data['date'] = $lesson->date instanceof Carbon
                ? $lesson->date->format('Y-m-d')
                : $lesson->date;

            return $data;
        })->values()->toArray();
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

    public ?int $editDisciplineId = 0;

    public ?int $editGroupId = 0;

    public bool $editIsPublished = false;

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
        $this->editDisciplineId = $lesson->discipline_id;
        $this->editGroupId = $lesson->group_id;
        $this->editIsPublished = $lesson->version?->status === 'published';
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
        $oldData = $isPublished ? [
            'teacher_id' => $lesson->teacher_id,
            'discipline_id' => $lesson->discipline_id,
            'lesson_number' => $lesson->lesson_number,
        ] : null;
        $lesson->update([
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
        $msg = $teacherChanged ? 'Замена сохранена. Часы пересчитаны.' : 'Занятие обновлено.';
        session()->flash('message', $msg);
    }

    public function deleteLesson(int $lessonId): void
    {
        ScheduleLesson::findOrFail($lessonId)->delete();
        $this->loadWeek();
        session()->flash('message', __('Lesson deleted successfully.'));
    }

    public function publish(int $versionId): void
    {
        $version = ScheduleVersion::findOrFail($versionId);
        // 10. Проверка на существующие опубликованные расписания в эти даты
        $overlap = ScheduleVersion::where('status', 'published')
            ->where('id', '!=', $version->id)
            ->where(function ($q) use ($version) {
                $q->whereBetween('date_from', [$version->date_from, $version->date_to])
                    ->orWhereBetween('date_to', [$version->date_from, $version->date_to]);
            })->first();
        if ($overlap) {
            session()->flash('error', "ОШИБКА: На эти даты уже опубликовано расписание «{$overlap->name}». Сначала переведите его в архив, чтобы компенсировать часы преподавателям.");

            return;
        }
        $version->publish(auth()->id());
        $this->loadWeek();
        session()->flash('message', 'Расписание опубликовано. Часы учтены (по 2 ч. за пару).');
    }

    public function addLesson(string $date = '', int $lessonNumber = 1, int $groupId = 0): void
    {
        $publishedVer = ScheduleVersion::where('status', 'published')->latest()->first();
        if ($publishedVer) {
            session()->flash('error', 'Расписание опубликовано. Сначала переведите его в черновик.');

            return;
        }
        $version = ScheduleVersion::whereIn('status', ['draft', 'generating'])
            ->latest()
            ->first();
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
        }
        if ($groupId <= 0) {
            $groupId = $this->viewMode === 'group' && $this->viewId > 0
                ? $this->viewId
                : Group::where('is_active', true)->first()?->id;
        }
        if (! $groupId) {
            session()->flash('error', 'Нет доступных групп.');

            return;
        }
        $exists = ScheduleLesson::where('version_id', $version->id)
            ->where('date', $date ?: $this->weekStart)
            ->where('lesson_number', $lessonNumber)
            ->where('group_id', $groupId)
            ->exists();
        if ($exists) {
            session()->flash('error', 'В этой ячейке уже есть занятие.');

            return;
        }
        $academicYear = AcademicYear::where('is_current', true)->first();
        $lesson = ScheduleLesson::create([
            'version_id' => $version->id,
            'date' => $date ?: $this->weekStart,
            'lesson_number' => $lessonNumber,
            'shift' => 1,
            'group_id' => $groupId,
            'discipline_id' => 1,
            'lesson_type_id' => 1,
            'teacher_id' => 1,
            'room_id' => 1,
            'building_id' => 1,
            'status' => 'draft',
        ]);
        $this->editLesson($lesson->id);
    }

    #[Computed]
    public function getGroupsProperty(): mixed
    {
        return Group::where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function getTeachersProperty(): mixed
    {
        return Teacher::where('is_active', true)
            ->orderBy('last_name')
            ->get();
    }

    #[Computed]
    public function getRoomsProperty(): mixed
    {
        return Room::where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function exportExcel(ExcelExportService $exportService): ?BinaryFileResponse
    {
        if (! $this->versionId) {
            session()->flash('error', 'Выберите версию расписания.');

            return null;
        }
        $version = ScheduleVersion::find($this->versionId);
        $deptId = $this->viewMode === 'department' && $this->viewId > 0
            ? $this->viewId
            : Department::first()->id;
        try {
            $filePath = $exportService->exportScheduleByDepartment(
                deptId: $deptId,
                dateFrom: Carbon::parse($this->weekStart),
                dateTo: Carbon::parse($this->weekStart),
                versionId: $version->id,
            );

            return response()->download($filePath, basename($filePath));
        } catch (\Exception $e) {
            session()->flash('error', 'Ошибка экспорта: '.$e->getMessage());

            return null;
        }
    }

    public ?array $autoFixResult = null;

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
        // 🔥 Оповещаем фронтенд, что нужно проскроллить
        $this->dispatch('scroll-to-highlight');
    }

    public function clearHighlights(): void
    {
        $this->highlightedLessonIds = [];
        $this->dispatch('clear-highlights');
    }

    public function autoFixConflicts(ConflictCheckerService $conflictChecker): void
    {
        $version = ScheduleVersion::whereIn('status', ['draft', 'published'])
            ->latest()
            ->first();
        if ($version === null) {
            session()->flash('info', 'Нет версии расписания для автоматического исправления.');

            return;
        }
        $this->autoFixResult = $conflictChecker->autoFix($version->id);
        $this->conflicts = $conflictChecker->checkVersion($version->id);
        session()->flash('message', $this->autoFixResult['message']);
    }

    public function checkConflicts(ConflictCheckerService $conflictChecker): void
    {
        $version = ScheduleVersion::whereIn('status', ['draft', 'published'])
            ->latest()
            ->first();
        if ($version === null) {
            session()->flash('info', __('No schedule version found to check.'));

            return;
        }
        $this->conflicts = $conflictChecker->checkVersion($version->id);
        $this->showConflictModal = true;
        $this->highlightedLessonIds = [];
    }

    public function checkConflictsForWeek(ConflictCheckerService $conflictChecker): void
    {
        $version = ScheduleVersion::whereIn('status', ['draft', 'published'])
            ->latest()
            ->first();
        if ($version === null) {
            session()->flash('info', __('No schedule version found to check.'));

            return;
        }
        if ($this->versionId === null) {
            $this->versionId = $version->id;
        }
        $weekStart = Carbon::parse($this->weekStart);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);
        $this->conflicts = $conflictChecker->checkVersionForRange(
            $version->id,
            $weekStart->format('Y-m-d'),
            $weekEnd->format('Y-m-d'),
        );
        $this->showConflictModal = true;
        $this->highlightedLessonIds = [];
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
        $conflictChecker->resolveConflict(
            conflictId: $this->resolvingConflictId,
            userId: auth()->id(),
            resolution: $this->resolutionNote,
        );
        $this->resolvingConflictId = null;
        $this->resolutionNote = '';
        $version = ScheduleVersion::whereIn('status', ['draft', 'published'])
            ->latest()
            ->first();
        if ($version !== null) {
            $this->conflicts = app(ConflictCheckerService::class)->checkVersion($version->id);
        }
        session()->flash('message', __('Conflict resolved successfully.'));
    }

    public function openShareModal(): void
    {
        $this->shareViewMode = $this->viewMode;
        $this->shareViewId = $this->viewId > 0 ? $this->viewId : 1;
        $this->shareLink = null;
        $this->showShareModal = true;
    }

    public string $shareDate = '';

    public string $shareType = 'week';

    public function generateShareLink(): void
    {
        $versionId = $this->versionId;
        if (! $versionId) {
            $version = ScheduleVersion::latest()->first();
            $versionId = $version?->id;
        }
        if ($this->shareType === 'day') {
            $date = $this->shareDate ?: Carbon::now()->format('Y-m-d');
            $this->shareLink = route('schedule.day', [
                'department' => $this->shareViewId,
                'date' => $date,
            ]);
        } else {
            $this->shareLink = route('schedule.shared', [
                'version' => $versionId,
                'viewMode' => $this->shareViewMode,
                'viewId' => $this->shareViewId,
            ]);
        }
        $this->showShareModal = false;
    }

    #[Computed]
    public function getRoomsForTeacherProperty(): mixed
    {
        if ($this->editTeacherId <= 0) {
            return Room::where('is_active', true)->orderBy('name')->get();
        }
        $priorityRooms = Room::where('is_active', true)
            ->whereHas('teacherRooms', fn ($q) => $q->where('teacher_id', $this->editTeacherId))
            ->orderBy(
                TeacherRoom::select('priority')
                    ->whereColumn('room_id', 'rooms.id')
                    ->where('teacher_id', $this->editTeacherId)
                    ->orderBy('priority')
                    ->limit(1)
            )
            ->get();
        if ($priorityRooms->isNotEmpty()) {
            return $priorityRooms;
        }

        return Room::where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function getDepartmentsProperty(): mixed
    {
        return Department::active()->orderBy('name')->get();
    }

    #[Computed]
    public function getDisciplinesProperty(): mixed
    {
        $query = CurriculumDiscipline::query()->orderBy('name');
        if ($this->editGroupId > 0) {
            $group = Group::with('curriculumPlans')->find($this->editGroupId);
            if ($group && $group->curriculumPlans->isNotEmpty()) {
                $planIds = $group->curriculumPlans->pluck('id');
                $query->whereIn('curriculum_plan_id', $planIds);
            }
        } elseif ($this->viewMode === 'group' && $this->viewId > 0) {
            $group = Group::with('curriculumPlans')->find($this->viewId);
            if ($group && $group->curriculumPlans->isNotEmpty()) {
                $planIds = $group->curriculumPlans->pluck('id');
                $query->whereIn('curriculum_plan_id', $planIds);
            }
        }

        return $query->get();
    }
}
