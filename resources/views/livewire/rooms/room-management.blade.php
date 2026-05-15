<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold">Управление аудиториями</h2>
            <p class="text-sm text-gray-500">Корпуса и аудитории</p>
        </div>
    </div>

    @if (session('success'))
        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-lg text-sm text-emerald-700 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-700 dark:text-red-300">
            {{ session('error') }}
        </div>
    @endif

    <!-- Tabs -->
    <div class="flex gap-1 bg-gray-100 dark:bg-gray-800 p-1 rounded-xl w-fit">
        <button wire:click="switchTab('rooms')" class="px-4 py-2 rounded-lg text-sm font-medium transition {{ $activeTab === 'rooms' ? 'bg-white dark:bg-gray-700 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}">
            Аудитории
        </button>
        <button wire:click="switchTab('buildings')" class="px-4 py-2 rounded-lg text-sm font-medium transition {{ $activeTab === 'buildings' ? 'bg-white dark:bg-gray-700 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900' }}">
            Корпуса
        </button>
    </div>

    @if ($activeTab === 'buildings')
        <!-- ─── BUILDINGS ─── -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-3">
                @forelse ($buildings as $building)
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 flex items-center justify-between">
                        <div>
                            <div class="font-medium">{{ $building->name }}</div>
                            <div class="text-sm text-gray-500">
                                {{ $building->short_name ? $building->short_name . ' · ' : '' }}{{ $building->floors_count }} этаж(а) · {{ $building->rooms_count }} аудиторий
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button wire:click="editBuilding({{ $building->id }})" class="px-3 py-1.5 text-sm rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 transition">Ред.</button>
                            <button wire:click="deleteBuilding({{ $building->id }})" wire:confirm="Удалить корпус?" class="px-3 py-1.5 text-sm rounded-lg bg-red-50 dark:bg-red-900/30 text-red-600 hover:bg-red-100 transition">Удал.</button>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-gray-400">Нет корпусов. Создайте первый корпус.</div>
                @endforelse
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold mb-4">{{ $editingBuildingId ? 'Редактировать корпус' : 'Новый корпус' }}</h3>
                <form wire:submit="saveBuilding" class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium mb-1">Название</label>
                        <input type="text" wire:model="buildingName" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                        @error('buildingName') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Короткое название</label>
                        <input type="text" wire:model="buildingShortName" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2" placeholder="К1, К2...">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Адрес</label>
                        <input type="text" wire:model="buildingAddress" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Этажей</label>
                        <input type="number" wire:model="buildingFloors" min="1" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                    </div>
                    <div class="flex gap-2 pt-2">
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition text-sm">
                            {{ $editingBuildingId ? 'Сохранить' : 'Создать' }}
                        </button>
                        @if ($showBuildingForm)
                            <button type="button" wire:click="cancelBuilding" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg transition text-sm">Отмена</button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    @else
        <!-- ─── ROOMS ─── -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Корпус</label>
                    <select wire:model.live="selectedBuildingId" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                        <option value="">Выберите корпус</option>
                        @foreach ($buildings as $building)
                            <option value="{{ $building->id }}">{{ $building->name }}</option>
                        @endforeach
                    </select>
                </div>

                @if ($selectedBuilding && $selectedBuilding->rooms->isNotEmpty())
                    @php $floors = $selectedBuilding->rooms->groupBy('floor')->sortKeys(); @endphp
                    @foreach ($floors as $floor => $floorRooms)
                        <div class="mb-4">
                            <h4 class="text-sm font-medium text-gray-500 mb-2">Этаж {{ $floor }}</h4>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                @foreach ($floorRooms as $room)
                                    <button wire:click="editRoom({{ $room->id }})" class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-emerald-400 dark:hover:border-emerald-600 text-left transition">
                                        <div class="font-medium">{{ $room->number }}</div>
                                        <div class="text-xs text-gray-500">{{ $room->roomType?->short_name ?? $room->roomType?->name ?? '' }}</div>
                                        <div class="text-xs text-gray-400">{{ $room->capacity }} мест</div>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @elseif ($selectedBuilding)
                    <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg text-gray-500 text-center">
                        Нет аудиторий в этом корпусе
                    </div>
                @else
                    <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg text-gray-500 text-center">
                        Выберите корпус
                    </div>
                @endif
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold mb-4">{{ $editingRoomId ? 'Редактировать аудиторию' : 'Новая аудитория' }}</h3>
                <form wire:submit="saveRoom" class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium mb-1">Корпус</label>
                        <select wire:model="selectedBuildingId" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            <option value="">Выберите корпус</option>
                            @foreach ($buildings as $building)
                                <option value="{{ $building->id }}">{{ $building->name }}</option>
                            @endforeach
                        </select>
                        @error('selectedBuildingId') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Номер</label>
                        <input type="text" wire:model="roomNumber" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2" placeholder="101, 2а, лаб-3">
                        @error('roomNumber') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Название</label>
                        <input type="text" wire:model="roomName" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2" placeholder="Лаборатория программирования">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Тип</label>
                        <select wire:model="roomTypeId" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            <option value="0">Выберите тип</option>
                            @foreach ($this->roomTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                        @error('roomTypeId') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Вместимость</label>
                        <input type="number" wire:model="capacity" min="1" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Этаж</label>
                        <input type="number" wire:model="floor" min="0" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="isAvailable" id="isAvailable" class="rounded border-gray-300">
                        <label for="isAvailable" class="text-sm">Доступна для бронирования</label>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-medium">Оборудование</label>
                            <button type="button" wire:click="addEquipment" class="text-xs text-emerald-600 hover:text-emerald-800">+ Добавить</button>
                        </div>
                        <div class="space-y-2">
                            @foreach ($equipment as $index => $item)
                                <div class="flex gap-2 items-center">
                                    <select wire:model="equipment.{{ $index }}.equipment_type_id" class="flex-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-2 py-1 text-sm">
                                        <option value="0">Выберите</option>
                                        @foreach ($this->equipmentTypes as $type)
                                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                                        @endforeach
                                    </select>
                                    <input type="number" wire:model="equipment.{{ $index }}.quantity" min="1" class="w-16 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-2 py-1 text-sm" placeholder="Кол-во">
                                    <button type="button" wire:click="removeEquipment({{ $index }})" class="text-red-600 hover:text-red-800">✕</button>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-medium">Преподаватели (приоритет)</label>
                            <button type="button" wire:click="addTeacherAssignment" class="text-xs text-emerald-600 hover:text-emerald-800">+ Добавить</button>
                        </div>
                        <div class="space-y-2">
                            @foreach ($teacherAssignments as $index => $ta)
                                <div class="flex gap-2 items-center">
                                    <select wire:model="teacherAssignments.{{ $index }}.teacher_id" class="flex-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-2 py-1 text-sm">
                                        <option value="0">Выберите преподавателя</option>
                                        @foreach ($allTeachers as $teacher)
                                            <option value="{{ $teacher->id }}">{{ $teacher->last_name }} {{ $teacher->first_name }}</option>
                                        @endforeach
                                    </select>
                                    <input type="number" wire:model="teacherAssignments.{{ $index }}.priority" min="0" class="w-16 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-2 py-1 text-sm" placeholder="Приор.">
                                    <label class="flex items-center gap-1 text-xs whitespace-nowrap">
                                        <input type="checkbox" wire:model="teacherAssignments.{{ $index }}.is_personal" class="rounded border-gray-300">
                                        Личная
                                    </label>
                                    <button type="button" wire:click="removeTeacherAssignment({{ $index }})" class="text-red-600 hover:text-red-800 text-sm">Удалить</button>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex gap-2 pt-2">
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition text-sm">
                            {{ $editingRoomId ? 'Сохранить' : 'Создать' }}
                        </button>
                        @if ($editingRoomId)
                            <button type="button" wire:click="deleteRoom({{ $editingRoomId }})" wire:confirm="Удалить аудиторию?" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition text-sm">Удалить</button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
