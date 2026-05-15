<?php

declare(strict_types=1);

namespace App\Http\Livewire;

use App\Models\ActivityLog;
use App\Models\CurriculumDiscipline;
use App\Models\Group;
use App\Models\Room;
use App\Models\ScheduleConflict;
use App\Models\ScheduleVersion;
use App\Models\Teacher;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Dashboard extends Component
{
    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.dashboard', [
            'groupsCount' => Group::count(),
            'teachersCount' => Teacher::count(),
            'roomsCount' => Room::count(),
            'disciplinesCount' => CurriculumDiscipline::count(),
            'schedulesCount' => ScheduleVersion::count(),
            'pendingConflicts' => ScheduleConflict::where('is_resolved', false)->count(),
            'recentActivity' => ActivityLog::latest()->take(5)->get(),
        ]);
    }
}
