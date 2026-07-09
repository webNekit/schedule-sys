<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Типы аудиторий</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Управление типами аудиторий</p>
        </div>
        <div class="mt-4 sm:mt-0 flex items-center gap-2">
            <button wire:click="create" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium transition-colors">
                + Создать тип
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
                        <th class="text-center px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Совместное использование</th>
                        <th class="text-center px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Аудиторий</th>
                        <th class="text-center px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Действия</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($roomTypes as $type)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full inline-block" style="background: {{ $type->color ?? '#94A3B8' }}"></span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $type->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-700 dark:text-gray-300">{{ $type->short_name ?? '—' }}</td>
                            <td class="px-6 py-4 text-center">
                                @if($type->can_be_shared)
                                    <span class="text-xs text-emerald-600 dark:text-emerald-400">Да</span>
                                @else
                                    <span class="text-xs text-gray-400">Нет</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center text-gray-700 dark:text-gray-300">{{ $type->rooms_count }}</td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button wire:click="edit({{ $type->id }})" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" title="Редактировать">
                                        Ред.
                                    </button>
                                    <button wire:click="delete({{ $type->id }})" wire:confirm="Удалить тип аудитории?" class="p-1.5 rounded-lg text-red-400 hover:text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-xs" title="Удалить">
                                        Удл.
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <p class="text-gray-400 dark:text-gray-500">Типы аудиторий не найдены</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($roomTypes->hasPages())
            <div class="px-6 py-3 border-t border-gray-100 dark:border-gray-700">
                {{ $roomTypes->links() }}
            </div>
        @endif
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" wire:click.self="cancel">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $editingId ? 'Редактировать тип' : 'Новый тип аудитории' }}</h3>
                </div>
                <form wire:submit="save" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Название <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="name" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                        <p class="text-xs text-gray-400 mt-1">Обязательное поле</p>
                        @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Короткое название</label>
                        <input type="text" wire:model="shortName" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                        <p class="text-xs text-gray-400 mt-1">Необязательное поле</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="canBeShared" id="canBeShared" class="rounded border-gray-300">
                        <label for="canBeShared" class="text-sm">Может использоваться совместно</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="isSportComplex" id="isSportComplex" class="rounded border-gray-300">
                        <label for="isSportComplex" class="text-sm">Выездной спорткомплекс</label>
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
