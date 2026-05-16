<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Преподаватели</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Список преподавателей и их нагрузка</p>
        </div>
        <div class="mt-4 sm:mt-0 flex items-center gap-2">
            <button wire:click="openImportModal"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                Импорт Excel
            </button>
            <button wire:click="openCreateModal"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium transition-colors">
                + Добавить преподавателя
            </button>
        </div>
    </div>

    @if (session('message'))
        <div
            class="p-3 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-lg text-sm text-emerald-700 dark:text-emerald-300">
            {{ session('message') }}
        </div>
    @endif
    @if (session('error'))
        <div
            class="p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-700 dark:text-red-300">
            {{ session('error') }}
        </div>
    @endif

    <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-4 border-b border-gray-100 dark:border-gray-700">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1">
                    <input type="text" wire:model.live="search" placeholder="Поиск преподавателей..."
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 px-4 py-2 text-sm text-gray-900 dark:text-gray-200 placeholder-gray-400 dark:placeholder-gray-500 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition-colors">
                </div>
                <select wire:model.live="departmentFilter"
                    class="rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 px-4 py-2 text-sm text-gray-900 dark:text-gray-200 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition-colors">
                    <option value="">Все отделения</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->short_name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="statusFilter"
                    class="rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 px-4 py-2 text-sm text-gray-900 dark:text-gray-200 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition-colors">
                    <option value="">Все статусы</option>
                    <option value="active">Активные</option>
                    <option value="inactive">Неактивные</option>
                </select>
                @if($search || $departmentFilter || $statusFilter)
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
                        <th class="text-left px-6 py-3 font-medium text-gray-500 dark:text-gray-400">ФИО</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Отделение</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Должность</th>
                        <th class="text-center px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Ставка</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Контакты</th>
                        <th class="text-center px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Статус</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($teachers as $teacher)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors cursor-pointer"
                            onclick="window.location='{{ route('teachers.show', $teacher) }}'">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-8 h-8 rounded-full bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-sm font-medium text-blue-600 dark:text-blue-400">
                                        {{ mb_substr($teacher->last_name, 0, 1) }}{{ mb_substr($teacher->first_name, 0, 1) }}
                                    </div>
                                    <div>
                                        <a href="{{ route('teachers.show', $teacher) }}" wire:navigate
                                            class="font-medium text-gray-900 dark:text-white hover:text-emerald-600 dark:hover:text-emerald-400">{{ $teacher->full_name }}</a>
                                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ $teacher->short_name }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                                {{ $teacher->department?->short_name ?? '—' }}</td>
                            <td class="px-6 py-4 text-gray-700 dark:text-gray-300">{{ $teacher->position?->name ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-center text-gray-700 dark:text-gray-300">
                                {{ $teacher->rate ? number_format($teacher->rate, 2) : '—' }}</td>
                            <td class="px-6 py-4">
                                <div class="text-gray-700 dark:text-gray-300 text-xs">
                                    @if($teacher->email)
                                        <span>{{ $teacher->email }}</span><br>
                                    @endif
                                    @if($teacher->phone)
                                        <span class="text-gray-400">{{ $teacher->phone }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($teacher->is_active)
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Активен
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                        Неактивен
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <p class="text-gray-400 dark:text-gray-500">Преподаватели не найдены</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($teachers->hasPages())
            <div class="px-6 py-3 border-t border-gray-100 dark:border-gray-700">
                {{ $teachers->links() }}
            </div>
        @endif
    </div>

    {{-- Модальное окно импорта --}}
    @if ($showImportModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" wire:click.self="closeImportModal">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
                <div class="p-5 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Импорт преподавателей</h3>
                    <button wire:click="closeImportModal" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>
                <form wire:submit="importExcel" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Выберите Excel-файл (.xlsx)</label>
                        <input type="file" wire:model="importFile" accept=".xlsx,.xls" required
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm">
                        @error('importFile') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="pt-4 flex justify-end gap-3 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" wire:click="closeImportModal"
                            class="px-4 py-2 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition">Отмена</button>
                        <button type="submit" wire:loading.attr="disabled"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm transition">
                            <span wire:loading.remove wire:target="importExcel">Импортировать</span>
                            <span wire:loading wire:target="importExcel">Загрузка...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Модальное окно создания --}}
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
            wire:click.self="$set('showCreateModal', false)">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Новый преподаватель</h3>
                </div>
                <form wire:submit="saveTeacher" class="p-6 space-y-4">
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-sm font-medium mb-1">Фамилия <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="newLastName"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm">
                            <p class="text-xs text-gray-400 mt-1">Обязательное поле</p>
                            @error('newLastName') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Имя <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="newFirstName"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm">
                            <p class="text-xs text-gray-400 mt-1">Обязательное поле</p>
                            @error('newFirstName') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Отчество</label>
                            <input type="text" wire:model="newMiddleName"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm">
                            <p class="text-xs text-gray-400 mt-1">Необязательное поле</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Отделение</label>
                            <select wire:model="newDepartmentId"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm">
                                <option value="">Не выбрано</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-400 mt-1">Выберите из списка</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Должность</label>
                            <select wire:model="newPositionId"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm">
                                <option value="">Не выбрана</option>
                                @foreach($positions as $pos)
                                    <option value="{{ $pos->id }}">{{ $pos->name }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-400 mt-1">Выберите из списка</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Тип занятости</label>
                            <select wire:model="newEmploymentType"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm">
                                <option value="full_time">Штатный</option>
                                <option value="part_time">Совместитель</option>
                                <option value="hourly">Почасовик</option>
                            </select>
                            <p class="text-xs text-gray-400 mt-1">Выберите из списка</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Ставка <span class="text-red-500">*</span></label>
                            <input type="number" wire:model="newRate" step="0.25" min="0" max="3"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm">
                            <p class="text-xs text-gray-400 mt-1">Введите число</p>
                            @error('newRate') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Email</label>
                            <input type="email" wire:model="newEmail"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm">
                            <p class="text-xs text-gray-400 mt-1">Введите email</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Телефон</label>
                            <input type="text" wire:model="newPhone"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm">
                            <p class="text-xs text-gray-400 mt-1">Необязательное поле</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="newIsActive" id="newIsActive" class="rounded border-gray-300">
                        <label for="newIsActive" class="text-sm">Активен</label>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" wire:click="$set('showCreateModal', false)"
                            class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 transition">Отмена</button>
                        <button type="submit"
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm transition">Создать</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>