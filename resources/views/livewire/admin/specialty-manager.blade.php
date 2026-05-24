<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold">Специальности</h2>
            <p class="text-sm text-gray-500">Управление специальностями (код, сроки обучения, бюджет/внебюджет)</p>
        </div>
        <div class="flex items-center gap-2">
            <button wire:click="openImportModal"
                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm transition">
                Импорт Excel
            </button>
            <button wire:click="create"
                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm transition">+
                Создать</button>
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

    {{-- Модальное окно импорта --}}
    @if ($showImportModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" wire:click.self="closeImportModal">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
                <div class="p-5 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Импорт специальностей</h3>
                    <button wire:click="closeImportModal" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>
                <div class="p-6 space-y-6">
                    <div class="text-sm text-gray-600 dark:text-gray-400 space-y-2">
                        <p>Для корректного импорта специальностей, пожалуйста, используйте наш стандартный шаблон.</p>
                        <p>Укажите код, название, сокращение и отделение. Система обновит данные, если код специальности уже существует.</p>
                    </div>

                    <div class="flex flex-col gap-3">
                        <a href="{{ asset('template/specialties_template.xlsx') }}" download
                            class="flex items-center justify-center gap-2 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg text-sm font-medium transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Скачать шаблон
                        </a>

                        <div class="relative">
                            <label class="flex flex-col items-center justify-center w-full px-4 py-6 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl hover:border-indigo-500 dark:hover:border-indigo-400 transition-colors cursor-pointer group">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-8 h-8 text-gray-400 group-hover:text-indigo-500 transition-colors mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                    <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Нажмите для выбора файла или перетащите</p>
                                    <p class="text-[10px] text-gray-400 mt-1">Excel (.xlsx, .xls)</p>
                                </div>
                                <input type="file" wire:model="importFile" class="hidden" accept=".xlsx,.xls">
                            </label>
                            @error('importFile') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <button type="button" wire:click="importExcel" wire:loading.attr="disabled"
                            class="flex items-center justify-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition-colors disabled:opacity-50">
                            <span wire:loading.remove wire:target="importExcel">Загрузить и импортировать</span>
                            <span wire:loading wire:target="importExcel">Загрузка...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div
        class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex gap-3">
                <input type="text" wire:model.live="search" placeholder="Поиск по коду или названию..."
                    class="flex-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm">
                <select wire:model.live="departmentFilter"
                    class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm">
                    <option value="">Все кафедры</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->short_name ?? $dept->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Код</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Название</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Кафедра</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Срок 9 кл</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Срок 11 кл</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Форма</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Бюджет</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Внебюджет</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-500">Действия</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($specialties as $spec)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 font-mono text-sm">{{ $spec->code }}</td>
                            <td class="px-4 py-3 font-medium">{{ $spec->name }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $spec->department?->short_name ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $spec->study_years_9 ?? $spec->study_years }} г.</td>
                            <td class="px-4 py-3">{{ $spec->study_years_11 ?? '—' }} г.</td>
                            <td class="px-4 py-3">{{ $spec->form_of_study ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $spec->budget_places ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $spec->contract_places ?? '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex justify-center gap-2">
                                    <button wire:click="edit({{ $spec->id }})"
                                        class="text-xs text-indigo-600 hover:text-indigo-800">Ред.</button>
                                    <button wire:click="delete({{ $spec->id }})" wire:confirm="Удалить специальность?"
                                        class="text-xs text-red-600 hover:text-red-800">Удал.</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-gray-400">Нет специальностей</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($specialties->hasPages())
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">{{ $specialties->links() }}</div>
        @endif
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" wire:click.self="cancel">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold">{{ $editingId ? 'Редактировать' : 'Создать' }} специальность</h3>
                </div>
                <form wire:submit="save" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Код</label>
                            <input type="text" wire:model="code"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2"
                                placeholder="09.02.07">
                            @error('code') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Короткое название</label>
                            <input type="text" wire:model="shortName"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2"
                                placeholder="ИСП">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Полное название</label>
                        <input type="text" wire:model="name"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                        @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Кафедра</label>
                        <select wire:model="departmentId"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            <option value="">Не выбрана</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Срок обучения для 9 кл (лет)</label>
                            <input type="number" wire:model="studyYears9" min="1" max="6"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Срок обучения для 11 кл (лет)</label>
                            <input type="number" wire:model="studyYears11" min="1" max="6"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Форма обучения</label>
                            <select wire:model="formOfStudy"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                                <option value="">Выберите</option>
                                <option value="очная">Очная</option>
                                <option value="заочная">Заочная</option>
                                <option value="очно-заочная">Очно-заочная</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Квалификация</label>
                            <input type="text" wire:model="qualification"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2"
                                placeholder="Техник-программист">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Бюджетные места</label>
                            <input type="number" wire:model="budgetPlaces" min="0"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Внебюджетные места</label>
                            <input type="number" wire:model="contractPlaces" min="0"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="isActive" id="isActive"
                            class="rounded border-gray-300 text-emerald-600">
                        <label for="isActive" class="text-sm">Активна</label>
                    </div>
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" wire:click="cancel"
                            class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800">Отмена</button>
                        <button type="submit"
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm">Сохранить</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>