<div class="space-y-6">
    <h2 class="text-2xl font-bold">Удаление данных</h2>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <p class="text-sm text-red-600 dark:text-red-400 mb-4">Внимание! Это действие необратимо. Выберите данные для удаления.</p>

        <div class="space-y-3">
            <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-900/50 cursor-pointer">
                <input type="checkbox" wire:model.live="deleteAll" class="rounded border-gray-300">
                <span class="font-medium">Выбрать всё</span>
            </label>

            <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-900/50 cursor-pointer">
                <input type="checkbox" wire:model.live="deleteGroups" class="rounded border-gray-300">
                <span>Группы ({{ $counts['groups'] ?? 0 }})</span>
            </label>
            <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-900/50 cursor-pointer">
                <input type="checkbox" wire:model.live="deleteSpecialties" class="rounded border-gray-300">
                <span>Специальности ({{ $counts['specialties'] ?? 0 }})</span>
            </label>
            <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-900/50 cursor-pointer">
                <input type="checkbox" wire:model.live="deleteCurriculum" class="rounded border-gray-300">
                <span>Учебные планы ({{ $counts['curriculum'] ?? 0 }})</span>
            </label>
            <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-900/50 cursor-pointer">
                <input type="checkbox" wire:model.live="deleteTeachers" class="rounded border-gray-300">
                <span>Преподаватели ({{ $counts['teachers'] ?? 0 }})</span>
            </label>
            <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-900/50 cursor-pointer">
                <input type="checkbox" wire:model.live="deleteRooms" class="rounded border-gray-300">
                <span>Аудитории ({{ $counts['rooms'] ?? 0 }})</span>
            </label>
            <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-900/50 cursor-pointer">
                <input type="checkbox" wire:model.live="deleteBuildings" class="rounded border-gray-300">
                <span>Здания ({{ $counts['buildings'] ?? 0 }})</span>
            </label>
            <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-900/50 cursor-pointer">
                <input type="checkbox" wire:model.live="deleteSchedule" class="rounded border-gray-300">
                <span>Расписание ({{ $counts['schedule'] ?? 0 }})</span>
            </label>
        </div>

        <div class="mt-6">
            <label class="block text-sm font-medium mb-1">Введите "УДАЛИТЬ" для подтверждения</label>
            <input type="text" wire:model="confirmText" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2" placeholder="УДАЛИТЬ">
        </div>

        <div class="flex gap-3 mt-4">
            <button wire:click="delete" wire:loading.attr="disabled" @if ($confirmText !== 'УДАЛИТЬ') disabled @endif class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition disabled:opacity-50">
                @if ($deleting) Удаление... @else Удалить @endif
            </button>
            <button wire:click="restore" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-lg transition">Восстановить</button>
        </div>
    </div>
</div>
