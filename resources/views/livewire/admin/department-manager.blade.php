<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Кафедры</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Управление кафедрами и отделениями</p>
        </div>
        <div class="mt-4 sm:mt-0 flex items-center gap-2">
            <button wire:click="create" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium transition-colors">
                + Создать кафедру
            </button>
        </div>
    </div>

    <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
                        <th class="text-left px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Название</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Коротко</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Заведующий</th>
                        <th class="text-center px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Специальностей</th>
                        <th class="text-center px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Преподавателей</th>
                        <th class="text-center px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Действия</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($departments as $department)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-6 py-4">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $department->name }}</p>
                                    @if($department->description)
                                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ Str::limit($department->description, 60) }}</p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-700 dark:text-gray-300">{{ $department->short_name ?? '—' }}</td>
                            <td class="px-6 py-4 text-gray-700 dark:text-gray-300">{{ $department->head_name ?? '—' }}</td>
                            <td class="px-6 py-4 text-center text-gray-700 dark:text-gray-300">{{ $department->specialties_count }}</td>
                            <td class="px-6 py-4 text-center text-gray-700 dark:text-gray-300">{{ $department->teachers_count }}</td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button wire:click="edit({{ $department->id }})" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" title="Редактировать">
                                        Ред.
                                    </button>
                                    <button wire:click="delete({{ $department->id }})" wire:confirm="Удалить кафедру?" class="p-1.5 rounded-lg text-red-400 hover:text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-xs" title="Удалить">
                                        Удл.
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <p class="text-gray-400 dark:text-gray-500">Кафедры не найдены</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($departments->hasPages())
            <div class="px-6 py-3 border-t border-gray-100 dark:border-gray-700">
                {{ $departments->links() }}
            </div>
        @endif
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" wire:click.self="cancel">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $editingId ? 'Редактировать кафедру' : 'Новая кафедра' }}</h3>
                </div>
                <form wire:submit="save" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Название</label>
                        <input type="text" wire:model="name" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                        @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Короткое название</label>
                            <input type="text" wire:model="shortName" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Заведующий</label>
                            <input type="text" wire:model="headName" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Телефон</label>
                            <input type="text" wire:model="phone" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Email</label>
                            <input type="email" wire:model="email" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Номер аудитории</label>
                        <input type="text" wire:model="roomNumber" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Описание</label>
                        <textarea wire:model="description" rows="3" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2"></textarea>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="isActive" id="isActive" class="rounded border-gray-300">
                        <label for="isActive" class="text-sm">Активна</label>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" wire:click="cancel" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 transition">Отмена</button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm transition">{{ $editingId ? 'Сохранить' : 'Создать' }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
