<?php

declare(strict_types=1);

namespace App\Http\Livewire\Admin;

use App\Models\Building;
use App\Models\CurriculumPlan;
use App\Models\Group;
use App\Models\Room;
use App\Models\ScheduleLesson;
use App\Models\Specialty;
use App\Models\Teacher;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class DeleteDataChecklist extends Component
{
    public bool $deleteGroups = false;

    public bool $deleteSpecialties = false;

    public bool $deleteCurriculum = false;

    public bool $deleteTeachers = false;

    public bool $deleteRooms = false;

    public bool $deleteBuildings = false;

    public bool $deleteSchedule = false;

    public bool $deleteAll = false;

    public string $confirmText = '';

    public array $counts = [];

    public bool $deleting = false;

    public function render(): View
    {
        return view('livewire.admin.delete-data-checklist');
    }

    public function updated($property): void
    {
        if ($property === 'deleteAll') {
            $this->deleteGroups = $this->deleteAll;
            $this->deleteSpecialties = $this->deleteAll;
            $this->deleteCurriculum = $this->deleteAll;
            $this->deleteTeachers = $this->deleteAll;
            $this->deleteRooms = $this->deleteAll;
            $this->deleteBuildings = $this->deleteAll;
            $this->deleteSchedule = $this->deleteAll;
        }

        $this->calculateCounts();
    }

    public function calculateCounts(): void
    {
        $this->counts = [];

        if ($this->deleteGroups) {
            $this->counts['groups'] = Group::count();
        }

        if ($this->deleteSpecialties) {
            $this->counts['specialties'] = Specialty::count();
        }

        if ($this->deleteCurriculum) {
            $this->counts['curriculum'] = CurriculumPlan::count();
        }

        if ($this->deleteTeachers) {
            $this->counts['teachers'] = Teacher::count();
        }

        if ($this->deleteRooms) {
            $this->counts['rooms'] = Room::count();
        }

        if ($this->deleteBuildings) {
            $this->counts['buildings'] = Building::count();
        }

        if ($this->deleteSchedule) {
            $this->counts['schedule'] = ScheduleLesson::count();
        }
    }

    public function delete(): void
    {
        if ($this->confirmText !== 'УДАЛИТЬ') {
            return;
        }

        $this->deleting = true;

        try {
            if ($this->deleteSchedule) {
                ScheduleLesson::query()->delete();
            }

            if ($this->deleteGroups) {
                Group::query()->delete();
            }

            if ($this->deleteSpecialties) {
                Specialty::query()->delete();
            }

            if ($this->deleteCurriculum) {
                CurriculumPlan::query()->delete();
            }

            if ($this->deleteTeachers) {
                Teacher::query()->delete();
            }

            if ($this->deleteRooms) {
                Room::query()->delete();
            }

            if ($this->deleteBuildings) {
                Building::query()->delete();
            }

            $this->counts = [];
            $this->confirmText = '';
        } finally {
            $this->deleting = false;
        }
    }

    public function restore(): void
    {
        if ($this->deleteSchedule) {
            ScheduleLesson::onlyTrashed()->restore();
        }

        if ($this->deleteGroups) {
            Group::onlyTrashed()->restore();
        }

        if ($this->deleteSpecialties) {
            Specialty::onlyTrashed()->restore();
        }

        if ($this->deleteCurriculum) {
            CurriculumPlan::onlyTrashed()->restore();
        }

        if ($this->deleteTeachers) {
            Teacher::onlyTrashed()->restore();
        }

        if ($this->deleteRooms) {
            Room::onlyTrashed()->restore();
        }

        if ($this->deleteBuildings) {
            Building::onlyTrashed()->restore();
        }

        $this->calculateCounts();
    }
}
