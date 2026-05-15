<?php

declare(strict_types=1);

namespace App\Http\Livewire\Admin;

use App\Models\Department;
use App\Models\Teacher;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class DepartmentManager extends Component
{
    use WithPagination;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $shortName = '';

    public ?int $headTeacherId = null;

    public bool $isActive = true;

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $department = Department::findOrFail($id);

        $this->editingId = $department->id;
        $this->name = $department->name;
        $this->shortName = $department->short_name ?? '';

        $teacher = Teacher::whereRaw(
            "TRIM(CONCAT(last_name, ' ', first_name, ' ', COALESCE(middle_name, ''))) = ?",
            [$department->head_name],
        )->first();

        $this->headTeacherId = $teacher?->id;
        $this->isActive = $department->is_active;

        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'shortName' => 'nullable|string|max:100',
            'headTeacherId' => 'nullable|integer|exists:teachers,id',
        ]);

        $headName = null;
        if ($this->headTeacherId) {
            $teacher = Teacher::find($this->headTeacherId);
            $headName = $teacher
                ? trim("{$teacher->last_name} {$teacher->first_name} {$teacher->middle_name}")
                : null;
        }

        $data = [
            'name' => $this->name,
            'short_name' => $this->shortName ?: null,
            'head_name' => $headName,
            'is_active' => $this->isActive,
        ];

        if ($this->editingId) {
            Department::findOrFail($this->editingId)->update($data);
        } else {
            Department::create($data);
        }

        $this->showForm = false;
        $this->resetForm();
    }

    public function delete(int $id): void
    {
        Department::findOrFail($id)->delete();
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->shortName = '';
        $this->headTeacherId = null;
        $this->isActive = true;
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.admin.department-manager', [
            'departments' => Department::withCount('teachers', 'groups', 'specialties')
                ->orderBy('name')
                ->paginate(20),
            'teachers' => Teacher::active()->orderBy('last_name')->get(),
        ]);
    }
}
