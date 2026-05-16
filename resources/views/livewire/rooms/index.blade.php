<div class="space-y-6">
    @if (session('message'))
        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-lg text-sm text-emerald-700 dark:text-emerald-300">
            {{ session('message') }}
        </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Аудитории</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Управление аудиторным фондом</p>
        </div>
        <div class="mt-4 sm:mt-0 flex items-center gap-2">
            <a href="{{ route('rooms.manage') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium transition-colors">
                + Управление аудиториями
            </a>
        </div>
    </div>

    <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-4 border-b border-gray-100 dark:border-gray-700">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1">
                    <input type="text" wire:model.live="search" placeholder="Поиск аудиторий..."
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 px-4 py-2 text-sm text-gray-900 dark:text-gray-200 placeholder-gray-400 dark:placeholder-gray-500 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition-colors">
                </div>
                <select wire:model.live="buildingFilter"
                    class="rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 px-4 py-2 text-sm text-gray-900 dark:text-gray-200 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition-colors">
                    <option value="">Все корпуса</option>
                    @foreach($buildings as $building)
                        <option value="{{ $building->id }}">{{ $building->short_name ?? $building->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="capacityFilter"
                    class="rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 px-4 py-2 text-sm text-gray-900 dark:text-gray-200 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition-colors">
                    <option value="">Вместимость</option>
                    <option value="small">До 15 мест</option>
                    <option value="medium">16-30 мест</option>
                    <option value="large">Более 30 мест</option>
                </select>
                @if($search || $buildingFilter || $capacityFilter)
                    <button wire:click="resetFilters"
                        class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
                        Сбросить
                    </button>
                @endif
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
                        <th class="text-left px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Аудитория</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Корпус</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Тип</th>
                        <th class="text-center px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Этаж</th>
                        <th class="text-center px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Вместимость</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Преподаватели</th>
                        <th class="text-center px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Статус</th>
                        <th class="text-center px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Действия</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($rooms as $room)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center text-sm font-bold text-purple-600 dark:text-purple-400">А</div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $room->number }}</p>
                                        @if($room->name)
                                            <p class="text-xs text-gray-400 dark:text-gray-500">{{ $room->name }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-700 dark:text-gray-300">{{ $room->building?->short_name ?? $room->building?->name ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                    {{ $room->roomType?->name ?? '—' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center text-gray-700 dark:text-gray-300">{{ $room->floor ?? '—' }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center gap-1 text-gray-700 dark:text-gray-300">
                                    @if($room->capacity)
                                        <span class="font-medium">{{ $room->capacity }}</span>
                                        <span class="text-xs text-gray-400">мест</span>
                                    @else
                                        —
                                    @endif
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($room->teacherRooms->isNotEmpty())
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($room->teacherRooms as $tr)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300">
                                                {{ $tr->teacher?->short_name ?? '—' }}
                                                @if($tr->is_personal)
                                                    <span class="ml-0.5 text-emerald-500" title="Личная">*</span>
                                                @endif
                                                @if($tr->priority > 0)
                                                    <span class="ml-0.5 text-gray-400">({{ $tr->priority }})</span>
                                                @endif
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($room->is_active)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Активна
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                        Неактивна
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button wire:click="editRoom({{ $room->id }})" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-xs" title="Редактировать">
                                        Ред.
                                    </button>
                                    <button wire:click="deleteRoom({{ $room->id }})" wire:confirm="Удалить аудиторию {{ $room->number }}?" class="p-1.5 rounded-lg text-red-400 hover:text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-xs" title="Удалить">
                                        Удал.
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <p class="text-gray-400 dark:text-gray-500">Аудитории не найдены</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($rooms->hasPages())
            <div class="px-6 py-3 border-t border-gray-100 dark:border-gray-700">
                {{ $rooms->links() }}
            </div>
        @endif
    </div>

    @if ($editingRoomId)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" wire:click.self="cancelEdit">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold">Редактировать аудиторию</h3>
                </div>
                <form wire:submit="saveRoom" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Корпус <span class="text-red-500">*</span></label>
                        <select wire:model="selectedBuildingId" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            <option value="">Выберите корпус</option>
                            @foreach($buildings as $building)
                                <option value="{{ $building->id }}">{{ $building->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Выберите из списка</p>
                        @error('selectedBuildingId') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Номер <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="roomNumber" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2" placeholder="101, 2а, лаб-3">
                        <p class="text-xs text-gray-400 mt-1">Обязательное поле</p>
                        @error('roomNumber') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Название</label>
                        <input type="text" wire:model="roomName" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                        <p class="text-xs text-gray-400 mt-1">Необязательное поле</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Тип <span class="text-red-500">*</span></label>
                        <select wire:model="roomTypeId" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            <option value="0">Выберите тип</option>
                            @foreach($roomTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Выберите из списка</p>
                        @error('roomTypeId') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Вместимость <span class="text-red-500">*</span></label>
                            <input type="number" wire:model="capacity" min="1" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            <p class="text-xs text-gray-400 mt-1">Введите число</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Этаж <span class="text-red-500">*</span></label>
                            <input type="number" wire:model="floor" min="0" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            <p class="text-xs text-gray-400 mt-1">Введите число</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="isAvailable" id="editIsAvailable" class="rounded border-gray-300">
                        <label for="editIsAvailable" class="text-sm">Доступна для бронирования</label>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" wire:click="cancelEdit" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 transition">Отмена</button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm transition">Сохранить</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
