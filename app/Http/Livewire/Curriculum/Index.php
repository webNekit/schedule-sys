<?php

declare(strict_types=1);

namespace App\Http\Livewire\Curriculum;

use App\Models\CurriculumPlan;
use App\Models\Department;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Index extends Component
{
    public string $search = '';

    public string $statusFilter = '';

    /** @var array<int, bool> */
    public array $expandedSpecialties = [];

    /** @var array<int, bool> */
    public array $expandedDepartments = [];

    public function toggleDepartment(int $id): void
    {
        $this->expandedDepartments[$id] = ! ($this->expandedDepartments[$id] ?? false);
    }

    public function toggleSpecialty(int $id): void
    {
        $this->expandedSpecialties[$id] = ! ($this->expandedSpecialties[$id] ?? false);
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->statusFilter = '';
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $plansQuery = CurriculumPlan::with(['specialty.department', 'academicYear', 'creator']);

        if ($this->search) {
            $plansQuery->where('name', 'like', '%'.$this->search.'%');
        }

        if ($this->statusFilter === 'active') {
            $plansQuery->where('is_active', true);
        } elseif ($this->statusFilter === 'inactive') {
            $plansQuery->where('is_active', false);
        }

        $plans = $plansQuery->orderBy('name')->get();

        $departments = Department::where('is_active', true)
            ->withWhereHas('specialties', function ($q) use ($plans) {
                $q->whereIn('id', $plans->pluck('specialty_id')->unique());
            })
            ->orderBy('name')
            ->get();

        return view('livewire.curriculum.index', [
            'departments' => $departments,
            'plans' => $plans,
        ]);
    }
}
