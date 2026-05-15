<?php

declare(strict_types=1);

namespace App\Http\Livewire\Admin;

use App\Models\Department;
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

    public string $headName = '';

    public string $phone = '';

    public string $email = '';

    public string $roomNumber = '';

    public string $description = '';

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
        $this->headName = $department->head_name ?? '';
        $this->phone = $department->phone ?? '';
        $this->email = $department->email ?? '';
        $this->roomNumber = $department->room_number ?? '';
        $this->description = $department->description ?? '';
        $this->isActive = $department->is_active;

        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'shortName' => 'nullable|string|max:100',
            'headName' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'roomNumber' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);

        $data = [
            'name' => $this->name,
            'short_name' => $this->shortName ?: null,
            'head_name' => $this->headName ?: null,
            'phone' => $this->phone ?: null,
            'email' => $this->email ?: null,
            'room_number' => $this->roomNumber ?: null,
            'description' => $this->description ?: null,
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
        $this->headName = '';
        $this->phone = '';
        $this->email = '';
        $this->roomNumber = '';
        $this->description = '';
        $this->isActive = true;
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.admin.department-manager', [
            'departments' => Department::withCount('teachers', 'groups', 'specialties')
                ->orderBy('name')
                ->paginate(20),
        ]);
    }
}
