<?php

declare(strict_types=1);

namespace App\Http\Livewire\Rooms;

use App\Models\Building;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $buildingFilter = '';

    public string $capacityFilter = '';

    public ?int $editingRoomId = null;

    public string $roomNumber = '';

    public string $roomName = '';

    public int $roomTypeId = 0;

    public int $capacity = 0;

    public int $floor = 1;

    public bool $isAvailable = true;

    public ?int $selectedBuildingId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedBuildingFilter(): void
    {
        $this->resetPage();
    }

    public function updatedCapacityFilter(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->buildingFilter = '';
        $this->capacityFilter = '';
    }

    public function editRoom(int $id): void
    {
        $room = Room::findOrFail($id);
        $this->editingRoomId = $room->id;
        $this->selectedBuildingId = $room->building_id;
        $this->roomNumber = $room->number;
        $this->roomName = $room->name ?? '';
        $this->roomTypeId = $room->room_type_id;
        $this->capacity = $room->capacity;
        $this->floor = $room->floor;
        $this->isAvailable = $room->is_available_for_booking;
    }

    public function saveRoom(): void
    {
        $this->validate([
            'roomNumber' => 'required|string|max:50',
            'roomName' => 'nullable|string|max:255',
            'roomTypeId' => 'required|integer|exists:room_types,id',
            'capacity' => 'required|integer|min:1',
            'floor' => 'required|integer|min:0',
            'isAvailable' => 'boolean',
            'selectedBuildingId' => 'required|integer|exists:buildings,id',
        ]);

        $room = Room::findOrFail($this->editingRoomId);
        $room->update([
            'building_id' => $this->selectedBuildingId,
            'number' => $this->roomNumber,
            'name' => $this->roomName ?: null,
            'room_type_id' => $this->roomTypeId,
            'capacity' => $this->capacity,
            'floor' => $this->floor,
            'is_available_for_booking' => $this->isAvailable,
        ]);

        $this->editingRoomId = null;
        $this->resetRoomForm();
        session()->flash('message', 'Аудитория обновлена');
    }

    public function deleteRoom(int $id): void
    {
        Room::findOrFail($id)->delete();
        session()->flash('message', 'Аудитория удалена');
    }

    public function cancelEdit(): void
    {
        $this->editingRoomId = null;
        $this->resetRoomForm();
    }

    private function resetRoomForm(): void
    {
        $this->roomNumber = '';
        $this->roomName = '';
        $this->roomTypeId = 0;
        $this->capacity = 0;
        $this->floor = 1;
        $this->isAvailable = true;
        $this->selectedBuildingId = null;
    }

    #[Layout('components.layouts.app')]
    public function render(): View
    {
        $query = Room::query()
            ->with(['building', 'roomType', 'teacherRooms.teacher']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('number', 'like', '%'.$this->search.'%')
                    ->orWhere('name', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->buildingFilter) {
            $query->where('building_id', $this->buildingFilter);
        }

        if ($this->capacityFilter === 'small') {
            $query->where('capacity', '<=', 15);
        } elseif ($this->capacityFilter === 'medium') {
            $query->whereBetween('capacity', [16, 30]);
        } elseif ($this->capacityFilter === 'large') {
            $query->where('capacity', '>', 30);
        }

        return view('livewire.rooms.index', [
            'rooms' => $query->latest()->paginate(15),
            'buildings' => Building::where('is_active', true)->orderBy('name')->get(),
            'roomTypes' => RoomType::orderBy('name')->get(),
        ]);
    }
}
