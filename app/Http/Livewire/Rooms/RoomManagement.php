<?php

declare(strict_types=1);

namespace App\Http\Livewire\Rooms;

use App\Models\Building;
use App\Models\EquipmentType;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Teacher;
use App\Models\TeacherRoom;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class RoomManagement extends Component
{
    // Building CRUD
    public bool $showBuildingForm = false;

    public ?int $editingBuildingId = null;

    public string $buildingName = '';

    public string $buildingShortName = '';

    public string $buildingAddress = '';

    public int $buildingFloors = 1;

    // Room CRUD
    public ?int $selectedBuildingId = null;

    public ?int $editingRoomId = null;

    public string $roomNumber = '';

    public string $roomName = '';

    public int $roomTypeId = 0;

    public int $capacity = 0;

    public int $floor = 1;

    public bool $isAvailable = true;

    public array $equipment = [];

    /** @var array<int, array{teacher_id: int, priority: int, is_personal: bool}> */
    public array $teacherAssignments = [];

    public string $activeTab = 'rooms';

    public function render(): View
    {
        return view('livewire.rooms.room-management', [
            'buildings' => Building::withCount('rooms')->orderBy('name')->get(),
            'selectedBuilding' => $this->selectedBuildingId
                ? Building::with('rooms.roomType')->find($this->selectedBuildingId)
                : null,
            'allTeachers' => Teacher::active()->orderBy('last_name')->orderBy('first_name')->get(),
        ]);
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    // ─── Building CRUD ───

    public function createBuilding(): void
    {
        $this->resetBuildingForm();
        $this->editingBuildingId = null;
        $this->showBuildingForm = true;
    }

    public function editBuilding(int $id): void
    {
        $building = Building::findOrFail($id);
        $this->editingBuildingId = $building->id;
        $this->buildingName = $building->name;
        $this->buildingShortName = $building->short_name ?? '';
        $this->buildingAddress = $building->address ?? '';
        $this->buildingFloors = $building->floors_count;
        $this->showBuildingForm = true;
    }

    public function saveBuilding(): void
    {
        $this->validate([
            'buildingName' => 'required|string|max:255',
            'buildingShortName' => 'nullable|string|max:50',
            'buildingAddress' => 'nullable|string|max:255',
            'buildingFloors' => 'required|integer|min:1',
        ]);

        $data = [
            'name' => $this->buildingName,
            'short_name' => $this->buildingShortName ?: null,
            'address' => $this->buildingAddress ?: null,
            'floors_count' => $this->buildingFloors,
        ];

        if ($this->editingBuildingId) {
            Building::findOrFail($this->editingBuildingId)->update($data);
            session()->flash('success', 'Корпус обновлён');
        } else {
            Building::create($data);
            session()->flash('success', 'Корпус создан');
        }

        $this->showBuildingForm = false;
        $this->resetBuildingForm();
    }

    public function deleteBuilding(int $id): void
    {
        $building = Building::withCount('rooms')->findOrFail($id);
        if ($building->rooms_count > 0) {
            session()->flash('error', 'Нельзя удалить корпус с аудиториями');

            return;
        }
        $building->delete();
        session()->flash('success', 'Корпус удалён');
    }

    public function cancelBuilding(): void
    {
        $this->showBuildingForm = false;
        $this->resetBuildingForm();
    }

    // ─── Room CRUD ───

    public function selectBuilding(int $id): void
    {
        $this->selectedBuildingId = $id;
        $this->editingRoomId = null;
        $this->resetRoomForm();
    }

    public function createRoom(): void
    {
        $this->resetRoomForm();
        $this->editingRoomId = null;
    }

    public function editRoom(int $roomId): void
    {
        $room = Room::with(['roomType', 'equipment', 'teacherRooms'])->findOrFail($roomId);

        $this->editingRoomId = $room->id;
        $this->selectedBuildingId = $room->building_id;
        $this->roomNumber = $room->number;
        $this->roomName = $room->name ?? '';
        $this->roomTypeId = $room->room_type_id;
        $this->capacity = $room->capacity;
        $this->floor = $room->floor;
        $this->isAvailable = $room->is_available_for_booking;
        $this->equipment = $room->equipment->map(function ($equip) {
            return [
                'equipment_type_id' => $equip->id,
                'quantity' => $equip->pivot->quantity ?? 1,
            ];
        })->toArray();
        $this->teacherAssignments = $room->teacherRooms->map(function (TeacherRoom $tr) {
            return [
                'teacher_id' => $tr->teacher_id,
                'priority' => $tr->priority,
                'is_personal' => $tr->is_personal,
            ];
        })->values()->toArray();
    }

    public function saveRoom(): void
    {
        $this->validate([
            'selectedBuildingId' => 'required|integer|exists:buildings,id',
            'roomNumber' => 'required|string|max:50',
            'roomName' => 'nullable|string|max:255',
            'roomTypeId' => 'required|integer|exists:room_types,id',
            'capacity' => 'required|integer|min:1',
            'floor' => 'required|integer|min:0',
            'isAvailable' => 'boolean',
        ]);

        $data = [
            'building_id' => $this->selectedBuildingId,
            'number' => $this->roomNumber,
            'name' => $this->roomName ?: null,
            'room_type_id' => $this->roomTypeId,
            'capacity' => $this->capacity,
            'floor' => $this->floor,
            'is_available_for_booking' => $this->isAvailable,
        ];

        $equipmentSync = collect($this->equipment)
            ->filter(fn (array $item) => ($item['equipment_type_id'] ?? 0) > 0)
            ->mapWithKeys(fn (array $item) => [
                $item['equipment_type_id'] => ['quantity' => $item['quantity'] ?? 1],
            ])
            ->toArray();

        if ($this->editingRoomId) {
            $room = Room::findOrFail($this->editingRoomId);
            $room->update($data);
            $room->equipment()->sync($equipmentSync);
            $this->syncTeacherAssignments($room);
            session()->flash('success', 'Аудитория обновлена');
        } else {
            $room = Room::create($data);
            $room->equipment()->sync($equipmentSync);
            $this->syncTeacherAssignments($room);
            session()->flash('success', 'Аудитория создана');
        }

        $this->editingRoomId = null;
        $this->resetRoomForm();
    }

    public function deleteRoom(int $roomId): void
    {
        Room::findOrFail($roomId)->delete();
        $this->editingRoomId = null;
        $this->resetRoomForm();
        session()->flash('success', 'Аудитория удалена');
    }

    // ─── Teacher Room Assignments ───

    public function addTeacherAssignment(): void
    {
        $this->teacherAssignments[] = [
            'teacher_id' => 0,
            'priority' => 0,
            'is_personal' => false,
        ];
    }

    public function removeTeacherAssignment(int $index): void
    {
        unset($this->teacherAssignments[$index]);
        $this->teacherAssignments = array_values($this->teacherAssignments);
    }

    public function syncTeacherAssignments(Room $room): void
    {
        $room->teacherRooms()->delete();

        foreach ($this->teacherAssignments as $assignment) {
            if (($assignment['teacher_id'] ?? 0) > 0) {
                TeacherRoom::create([
                    'room_id' => $room->id,
                    'teacher_id' => $assignment['teacher_id'],
                    'priority' => $assignment['priority'] ?? 0,
                    'is_personal' => $assignment['is_personal'] ?? false,
                ]);
            }
        }
    }

    public function addEquipment(): void
    {
        $this->equipment[] = [
            'equipment_type_id' => 0,
            'quantity' => 1,
        ];
    }

    public function removeEquipment(int $index): void
    {
        unset($this->equipment[$index]);
        $this->equipment = array_values($this->equipment);
    }

    // ─── Computed ───

    #[Computed]
    public function roomTypes(): Collection
    {
        return RoomType::orderBy('name')->get();
    }

    #[Computed]
    public function equipmentTypes(): Collection
    {
        return EquipmentType::orderBy('name')->get();
    }

    // ─── Reset ───

    private function resetBuildingForm(): void
    {
        $this->buildingName = '';
        $this->buildingShortName = '';
        $this->buildingAddress = '';
        $this->buildingFloors = 1;
        $this->editingBuildingId = null;
    }

    private function resetRoomForm(): void
    {
        $this->roomNumber = '';
        $this->roomName = '';
        $this->roomTypeId = 0;
        $this->capacity = 0;
        $this->floor = 1;
        $this->isAvailable = true;
        $this->equipment = [];
        $this->teacherAssignments = [];
    }
}
