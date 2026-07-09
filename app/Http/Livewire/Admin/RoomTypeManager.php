<?php

declare(strict_types=1);

namespace App\Http\Livewire\Admin;

use App\Models\RoomType;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class RoomTypeManager extends Component
{
    use WithPagination;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $shortName = '';

    public bool $canBeShared = true;

    public bool $isSportComplex = false;

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $type = RoomType::findOrFail($id);

        $this->editingId = $type->id;
        $this->name = $type->name;
        $this->shortName = $type->short_name ?? '';
        $this->canBeShared = $type->can_be_shared;
        $this->isSportComplex = $type->is_sport_complex;

        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'shortName' => 'nullable|string|max:50',
            'canBeShared' => 'boolean',
            'isSportComplex' => 'boolean',
        ]);

        $data = [
            'name' => $this->name,
            'short_name' => $this->shortName ?: null,
            'can_be_shared' => $this->canBeShared,
            'is_sport_complex' => $this->isSportComplex,
        ];

        if ($this->editingId) {
            RoomType::findOrFail($this->editingId)->update($data);
        } else {
            RoomType::create($data);
        }

        $this->showForm = false;
        $this->resetForm();
    }

    public function delete(int $id): void
    {
        RoomType::findOrFail($id)->delete();
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
        $this->canBeShared = true;
        $this->isSportComplex = false;
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.admin.room-type-manager', [
            'roomTypes' => RoomType::withCount('rooms')
                ->orderBy('name')
                ->paginate(20),
        ]);
    }
}
