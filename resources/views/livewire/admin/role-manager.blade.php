<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold">Управление ролями</h2>
        <button wire:click="create" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium transition cursor-pointer">
            + Создать роль
        </button>
    </div>

    @if (session('error'))
        <div class="p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-700 dark:text-red-300">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <input
                wire:model.live.debounce.300ms="search"
                type="text"
                placeholder="Поиск ролей..."
                class="w-full max-w-xs px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
            >
        </div>

        <table class="w-full">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-900/50 text-sm text-gray-500 dark:text-gray-400">
                    <th class="text-left px-4 py-3 font-medium">Название</th>
                    <th class="text-left px-4 py-3 font-medium">Слаг</th>
                    <th class="text-left px-4 py-3 font-medium">Описание</th>
                    <th class="text-center px-4 py-3 font-medium">Пользователей</th>
                    <th class="text-right px-4 py-3 font-medium">Действия</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($this->roles as $role)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                        <td class="px-4 py-3 font-medium">{{ $role->name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $role->slug }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $role->description }}</td>
                        <td class="px-4 py-3 text-center text-sm">{{ $role->users_count }}</td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <button wire:click="edit({{ $role->id }})" class="px-3 py-1.5 text-sm bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 rounded-lg transition cursor-pointer">
                                Редактировать
                            </button>
                            <button wire:click="delete({{ $role->id }})" wire:confirm="Вы уверены, что хотите удалить роль «{{ $role->name }}»?" class="px-3 py-1.5 text-sm bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/50 rounded-lg transition cursor-pointer">
                                Удалить
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            Роли не найдены.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($this->roles->hasPages())
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                {{ $this->roles->links() }}
            </div>
        @endif
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" wire:click.self="cancel">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold">{{ $editId ? 'Редактировать роль' : 'Создать роль' }}</h3>
                </div>

                <form wire:submit="save" class="p-6 space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Название <span class="text-red-500">*</span></label>
                            <input wire:model="name" type="text" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition">
                            <p class="text-xs text-gray-400 mt-1">Обязательное поле</p>
                            @error('name') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Слаг <span class="text-red-500">*</span></label>
                            <input wire:model="slug" type="text" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition">
                            <p class="text-xs text-gray-400 mt-1">Обязательное поле</p>
                            @error('slug') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Описание</label>
                        <textarea wire:model="description" rows="2" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"></textarea>
                        <p class="text-xs text-gray-400 mt-1">Необязательное поле</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-3">Разрешения</label>
                        <div class="space-y-4">
                            @foreach ($this->groupedPermissions as $module => $group)
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 uppercase tracking-wide">{{ $group['label'] }}</h4>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        @foreach ($group['permissions'] as $permission)
                                            <label class="flex items-center space-x-2 px-3 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/30 transition cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    wire:model="selectedPermissions"
                                                    value="{{ $permission->id }}"
                                                    class="rounded border-gray-300 dark:border-gray-600 text-emerald-600 focus:ring-emerald-500 cursor-pointer"
                                                >
                                                <span class="text-sm">{{ $permission->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @error('selectedPermissions') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" wire:click="cancel" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition cursor-pointer">
                            Отмена
                        </button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium transition cursor-pointer">
                            {{ $editId ? 'Сохранить' : 'Создать' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
