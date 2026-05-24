<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Специальности</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Управление справочником специальностей</p>
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
        </div>
    </div>

    @if (session('message'))
        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-lg text-sm text-emerald-700 dark:text-emerald-300">
            {{ session('message') }}
        </div>
    @endif

    <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex gap-3">
            <div class="flex-1">
                <input type="text" wire:model.live="search" placeholder="Поиск специальностей..."
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 px-4 py-2 text-sm">
            </div>
            <select wire:model.live="departmentFilter" class="rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 px-4 py-2 text-sm">
                <option value="">Все отделения</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->short_name }}</option>
                @endforeach
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
                        <th class="text-left px-6 py-3 font-medium text-gray-500">Код</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-500">Название</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-500">Отделение</th>
                        <th class="text-center px-6 py-3 font-medium text-gray-500">Курсов</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($specialties as $spec)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-6 py-4 font-mono text-xs text-gray-500">{{ $spec->code }}</td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $spec->name }}</div>
                                <div class="text-xs text-gray-400">{{ $spec->short_name }}</div>
                            </td>
                            <td class="px-6 py-4">{{ $spec->department?->short_name }}</td>
                            <td class="px-6 py-4 text-center">{{ $spec->max_courses }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-gray-400">Специальности не найдены</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($specialties->hasPages())
            <div class="px-6 py-3 border-t border-gray-100 dark:border-gray-700">
                {{ $specialties->links() }}
            </div>
        @endif
    </div>

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
</div>
