<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold">Управление пользователями</h2>
        <button wire:click="create" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium transition cursor-pointer">
            + Создать пользователя
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
                placeholder="Поиск пользователей..."
                class="w-full max-w-xs px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition"
            >
        </div>

        <table class="w-full">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-900/50 text-sm text-gray-500 dark:text-gray-400">
                    <th class="text-left px-4 py-3 font-medium">Имя</th>
                    <th class="text-left px-4 py-3 font-medium">Email</th>
                    <th class="text-left px-4 py-3 font-medium">Роли</th>
                    <th class="text-center px-4 py-3 font-medium">Статус</th>
                    <th class="text-right px-4 py-3 font-medium">Действия</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($this->users as $user)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                        <td class="px-4 py-3 font-medium">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                @foreach ($user->roles as $role)
                                    <span class="px-2 py-0.5 text-xs rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                        {{ $role->name }}
                                    </span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button wire:click="toggleActive({{ $user->id }})" class="cursor-pointer">
                                @if ($user->is_active)
                                    <span class="px-2 py-0.5 text-xs rounded-full bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 border border-green-200 dark:border-green-800">
                                        Активен
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 text-xs rounded-full bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800">
                                        Неактивен
                                    </span>
                                @endif
                            </button>
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <button wire:click="edit({{ $user->id }})" class="px-3 py-1.5 text-sm bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 rounded-lg transition cursor-pointer">
                                Редактировать
                            </button>
                            <button wire:click="delete({{ $user->id }})" wire:confirm="Вы уверены, что хотите удалить пользователя «{{ $user->name }}»?" class="px-3 py-1.5 text-sm bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/50 rounded-lg transition cursor-pointer">
                                Удалить
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            Пользователи не найдены.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($this->users->hasPages())
            <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                {{ $this->users->links() }}
            </div>
        @endif
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" wire:click.self="cancel">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold">{{ $editId ? 'Редактировать пользователя' : 'Создать пользователя' }}</h3>
                </div>

                <form wire:submit="save" class="p-6 space-y-5">
                    <div>
                        <label class="block text-sm font-medium mb-1">Имя</label>
                        <input wire:model="name" type="text" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition">
                        @error('name') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Email</label>
                        <input wire:model="email" type="email" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition">
                        @error('email') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Пароль</label>
                            <input wire:model="password" type="password" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition" placeholder="{{ $editId ? 'Оставьте пустым' : '' }}">
                            @error('password') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Подтверждение</label>
                            <input wire:model="passwordConfirmation" type="password" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Роли</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach ($this->roles as $role)
                                <label class="flex items-center space-x-2 px-3 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/30 transition cursor-pointer">
                                    <input
                                        type="checkbox"
                                        wire:model="selectedRoles"
                                        value="{{ $role->id }}"
                                        class="rounded border-gray-300 dark:border-gray-600 text-emerald-600 focus:ring-emerald-500 cursor-pointer"
                                    >
                                    <span class="text-sm">{{ $role->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('selectedRoles') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center space-x-2">
                        <input
                            type="checkbox"
                            wire:model="isActive"
                            id="isActive"
                            class="rounded border-gray-300 dark:border-gray-600 text-emerald-600 focus:ring-emerald-500 cursor-pointer"
                        >
                        <label for="isActive" class="text-sm font-medium cursor-pointer">Активен</label>
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
