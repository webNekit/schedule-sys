<div class="space-y-6">
    <h2 class="text-2xl font-bold">Импорт учебного плана</h2>

    @if (session('message'))
        <div
            class="p-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-lg text-emerald-700 dark:text-emerald-300">
            {{ session('message') }}
        </div>
    @endif

    @if (session('error'))
        <div
            class="p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg text-red-700 dark:text-red-300">
            {{ session('error') }}
        </div>
    @endif

    @if (session('warning'))
        <div
            class="p-4 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-lg text-amber-700 dark:text-amber-300">
            {{ session('warning') }}
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <form wire:submit="import" class="space-y-5">
            <div>
                <label class="block text-sm font-medium mb-1">Файл учебного плана (XML, XLSX, XLS) <span class="text-red-500">*</span></label>
                <input type="file" wire:model="xmlFile" accept=".xml,.xlsx,.xls"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                <p class="text-xs text-gray-400 mt-1">Обязательное поле</p>
                @error('xmlFile') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Специальность <span class="text-red-500">*</span></label>
                    <select wire:model="specialtyId"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                        <option value="">Выберите специальность</option>
                        @foreach ($specialties as $specialty)
                            <option value="{{ $specialty->id }}">{{ $specialty->name }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Выберите из списка</p>
                    @error('specialtyId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Учебный год <span class="text-red-500">*</span></label>
                    <select wire:model="academicYearId"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                        <option value="">Выберите год</option>
                        @foreach ($academicYears as $year)
                            <option value="{{ $year->id }}">{{ $year->name }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Выберите из списка</p>
                    @error('academicYearId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="pt-2 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                <span class="text-sm text-gray-500">Импорт Excel файла может занять до 10 секунд.</span>

                {{-- Умная кнопка с лоадером --}}
                <button type="submit" wire:loading.attr="disabled" wire:target="import, xmlFile"
                    class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-medium rounded-lg transition-colors">

                    {{-- Спиннер (показывается только во время загрузки файла или парсинга) --}}
                    <svg wire:loading wire:target="import" class="animate-spin h-5 w-5 text-white"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>

                    {{-- Текст до нажатия --}}
                    <span wire:loading.remove wire:target="import">
                        Импортировать план
                    </span>

                    {{-- Текст во время работы метода import() --}}
                    <span wire:loading wire:target="import">
                        Чтение и сохранение...
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>