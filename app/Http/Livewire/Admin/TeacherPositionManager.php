<?php

declare(strict_types=1);

namespace App\Http\Livewire\Admin;

use App\Models\TeacherPosition;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class TeacherPositionManager extends Component
{
    use WithPagination;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public bool $isActive = true;

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $position = TeacherPosition::findOrFail($id);

        $this->editingId = $position->id;
        $this->name = $position->name;
        $this->isActive = $position->is_active;

        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
        ]);

        $data = [
            'name' => $this->name,
            'is_active' => $this->isActive,
        ];

        if ($this->editingId) {
            TeacherPosition::findOrFail($this->editingId)->update($data);
        } else {
            TeacherPosition::create($data);
        }

        $this->showForm = false;
        $this->resetForm();
    }

    public function delete(int $id): void
    {
        TeacherPosition::findOrFail($id)->delete();
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
        $this->isActive = true;
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.admin.teacher-position-manager', [
            'positions' => TeacherPosition::withCount('teachers')
                ->orderBy('name')
                ->paginate(20),
        ]);
    }
}
