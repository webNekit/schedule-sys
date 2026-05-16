<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Группы</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Управление учебными группами</p>
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
                + Добавить группу
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
                    <input type="text" wire:model.live="search" placeholder="Поиск групп..."
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 px-4 py-2 text-sm text-gray-900 dark:text-gray-200 placeholder-gray-400 dark:placeholder-gray-500 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition-colors">
                </div>
                <select wire:model.live="departmentFilter"
                    class="rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 px-4 py-2 text-sm text-gray-900 dark:text-gray-200 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition-colors">
                    <option value="">Все отделения</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->short_name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="courseFilter"
                    class="rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 px-4 py-2 text-sm text-gray-900 dark:text-gray-200 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition-colors">
                    <option value="">Все курсы</option>
                    @foreach($courses as $course)
                        <option value="{{ $course }}">{{ $course }} курс</option>
                    @endforeach
                </select>
                <select wire:model.live="statusFilter"
                    class="rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 px-4 py-2 text-sm text-gray-900 dark:text-gray-200 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition-colors">
                    <option value="">Все статусы</option>
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                @if($search || $departmentFilter || $courseFilter || $statusFilter)
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
                        <th class="text-left px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Название</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Специальность</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Отделение</th>
                        <th class="text-center px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Курс</th>
                        <th class="text-center px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Студентов</th>
                        <th class="text-center px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Статус</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($groups as $group)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors cursor-pointer"
                            onclick="window.location='{{ route('groups.show', $group) }}'">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center text-sm font-bold text-emerald-600 dark:text-emerald-400">
                                        Г</div>
                                    <div>
                                        <a href="{{ route('groups.show', $group) }}" wire:navigate
                                            class="font-medium text-gray-900 dark:text-white hover:text-emerald-600 dark:hover:text-emerald-400">{{ $group->name }}</a>
                                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ $group->short_name }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                                {{ $group->specialty?->short_name ?? '—' }}</td>
                            <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                                {{ $group->department?->short_name ?? '—' }}</td>
                            <td class="px-6 py-4 text-center">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                    {{ $group->current_course }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center text-gray-700 dark:text-gray-300">
                                {{ $group->students_count ?? '—' }}</td>
                            <td class="px-6 py-4 text-center">
                                @if($group->is_active && $group->status !== 'graduated')
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Активна
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                        {{ $group->status === 'graduated' ? 'Выпущена' : 'Неактивна' }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <p class="text-gray-400 dark:text-gray-500">Группы не найдены</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($groups->hasPages())
            <div class="px-6 py-3 border-t border-gray-100 dark:border-gray-700">
                {{ $groups->links() }}
            </div>
        @endif
    </div>

    {{-- Модальное окно импорта --}}
    @if ($showImportModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" wire:click.self="closeImportModal">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
                <div class="p-5 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Импорт групп</h3>
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

    @if($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
            wire:click.self="$set('showCreateModal', false)">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Новая группа</h3>
                </div>
                <form wire:submit="saveGroup" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Название <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="newName"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm">
                            <p class="text-xs text-gray-400 mt-1">Обязательное поле</p>
                            @error('newName') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Короткое название</label>
                            <input type="text" wire:model="newShortName"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm">
                            <p class="text-xs text-gray-400 mt-1">Необязательное поле</p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Специальность</label>
                        <select wire:model="newSpecialtyId"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm">
                            <option value="">Не выбрана</option>
                            @foreach($specialties as $spec)
                                <option value="{{ $spec->id }}">{{ $spec->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Выберите из списка</p>
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
                            <label class="block text-sm font-medium mb-1">Учебный год</label>
                            <select wire:model="newAcademicYearId"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm">
                                <option value="">Не выбран</option>
                                @foreach($academicYears as $year)
                                    <option value="{{ $year->id }}">{{ $year->name }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-400 mt-1">Выберите из списка</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Курс <span class="text-red-500">*</span></label>
                            <input type="number" wire:model="newCourse" min="1" max="6"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm">
                            <p class="text-xs text-gray-400 mt-1">Обязательное поле</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Студентов</label>
                            <input type="number" wire:model="newStudentsCount" min="0"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm">
                            <p class="text-xs text-gray-400 mt-1">Введите число</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Год поступления</label>
                            <input type="number" wire:model="newEnrollmentYear" min="2000" max="2100"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm">
                            <p class="text-xs text-gray-400 mt-1">Введите год</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Статус</label>
                            <select wire:model="newStatus"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm">
                                <option value="active">Активна</option>
                                <option value="graduated">Выпущена</option>
                                <option value="academic_leave">Академ. отпуск</option>
                            </select>
                            <p class="text-xs text-gray-400 mt-1">Выберите из списка</p>
                        </div>
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