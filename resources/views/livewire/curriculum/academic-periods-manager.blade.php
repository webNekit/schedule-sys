<div class="space-y-6">
    <h2 class="text-2xl font-bold">Учебные периоды</h2>

    @if (session('message'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-lg text-emerald-700 dark:text-emerald-300">
            {{ session('message') }}
        </div>
    @endif

    @if (session('error'))
        <div class="p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg text-red-700 dark:text-red-300">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Учебные годы</h3>
                    <button wire:click="importHolidays" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition">
                        Импорт праздников
                    </button>
                </div>

                <div class="space-y-3">
                    @foreach ($academicYears as $year)
                        <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 {{ $year->is_current ? 'border-emerald-400 dark:border-emerald-600 bg-emerald-50/50 dark:bg-emerald-900/10' : '' }}">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="font-medium">{{ $year->name }}</div>
                                    <div class="text-sm text-gray-500">
                                        {{ $year->date_start->format('d.m.Y') }} — {{ $year->date_end->format('d.m.Y') }}
                                    </div>
                                    <div class="text-xs text-gray-400 mt-1">
                                        Семестры: {{ $year->first_semester_start->format('d.m.Y') }}—{{ $year->first_semester_end->format('d.m.Y') }},
                                        {{ $year->second_semester_start->format('d.m.Y') }}—{{ $year->second_semester_end->format('d.m.Y') }}
                                    </div>
                                    @if ($year->is_current)
                                        <span class="inline-block mt-1 text-xs px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full">Текущий</span>
                                    @endif
                                </div>
                                <div class="flex gap-2">
                                    <button wire:click="editYear({{ $year->id }})" class="text-sm text-indigo-600 hover:text-indigo-800">Ред.</button>
                                    <button wire:click="deleteYear({{ $year->id }})" wire:confirm="Удалить учебный год?" class="text-sm text-red-600 hover:text-red-800">Удл.</button>
                                </div>
                            </div>

                            @if ($year->vacations->isNotEmpty())
                                <div class="mt-3 pl-4 border-l-2 border-gray-200 dark:border-gray-600 space-y-2">
                                    <div class="text-xs font-medium text-gray-500">Каникулы:</div>
                                    @foreach ($year->vacations as $vacation)
                                        <div class="flex items-start justify-between text-sm">
                                            <div>
                                                <span class="font-medium">{{ $vacation->name }}</span>
                                                <span class="text-gray-500 ml-2">{{ $vacation->start_date->format('d.m.Y') }} — {{ $vacation->end_date->format('d.m.Y') }}</span>
                                            </div>
                                            <div class="flex gap-2">
                                                <button wire:click="editVacation({{ $vacation->id }})" class="text-xs text-indigo-600 hover:text-indigo-800">Ред.</button>
                                                <button wire:click="deleteVacation({{ $vacation->id }})" wire:confirm="Удалить каникулы?" class="text-xs text-red-600 hover:text-red-800">Удл.</button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <button wire:click="$set('vacationYearId', {{ $year->id }})" class="mt-2 text-xs text-emerald-600 hover:text-emerald-800">+ Добавить каникулы</button>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold mb-4">{{ $editingYearId ? 'Редактирование года' : 'Новый учебный год' }}</h3>
                <form wire:submit="saveYear" class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium mb-1">Название <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="name" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                        <p class="text-xs text-gray-400 mt-1">Обязательное поле</p>
                        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-sm font-medium mb-1">Год начала <span class="text-red-500">*</span></label>
                            <input type="number" wire:model="yearStart" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            <p class="text-xs text-gray-400 mt-1">Введите число</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Год конца <span class="text-red-500">*</span></label>
                            <input type="number" wire:model="yearEnd" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            <p class="text-xs text-gray-400 mt-1">Введите число</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-sm font-medium mb-1">Дата начала <span class="text-red-500">*</span></label>
                            <input type="date" wire:model="dateStart" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            <p class="text-xs text-gray-400 mt-1">Выберите дату</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Дата конца <span class="text-red-500">*</span></label>
                            <input type="date" wire:model="dateEnd" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            <p class="text-xs text-gray-400 mt-1">Выберите дату</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-sm font-medium mb-1">1 семестр (с) <span class="text-red-500">*</span></label>
                            <input type="date" wire:model="firstSemesterStart" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            <p class="text-xs text-gray-400 mt-1">Выберите дату</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">1 семестр (по) <span class="text-red-500">*</span></label>
                            <input type="date" wire:model="firstSemesterEnd" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            <p class="text-xs text-gray-400 mt-1">Выберите дату</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-sm font-medium mb-1">2 семестр (с) <span class="text-red-500">*</span></label>
                            <input type="date" wire:model="secondSemesterStart" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            <p class="text-xs text-gray-400 mt-1">Выберите дату</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">2 семестр (по) <span class="text-red-500">*</span></label>
                            <input type="date" wire:model="secondSemesterEnd" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            <p class="text-xs text-gray-400 mt-1">Выберите дату</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="isCurrent" id="isCurrent" class="rounded border-gray-300">
                        <label for="isCurrent" class="text-sm">Текущий учебный год</label>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition">
                            {{ $editingYearId ? 'Сохранить' : 'Создать' }}
                        </button>
                        @if ($editingYearId)
                            <button type="button" wire:click="cancelEdit" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-lg transition">Отмена</button>
                        @endif
                    </div>
                </form>
            </div>

            @if ($vacationYearId)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold mb-4">{{ $editingVacationId ? 'Редактирование каникул' : 'Добавление каникул' }}</h3>
                    <form wire:submit="{{ $editingVacationId ? 'updateVacation' : 'addVacation(' . $vacationYearId . ')' }}" class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium mb-1">Название <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="vacationName" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                            <p class="text-xs text-gray-400 mt-1">Обязательное поле</p>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-sm font-medium mb-1">Дата начала <span class="text-red-500">*</span></label>
                                <input type="date" wire:model.live="vacationStartDate" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                                <p class="text-xs text-gray-400 mt-1">Выберите дату</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Дата конца <span class="text-red-500">*</span></label>
                                <input type="date" wire:model.live="vacationEndDate" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                                <p class="text-xs text-gray-400 mt-1">Выберите дату</p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Длительность (дней)</label>
                            <input type="number" wire:model="vacationDurationDays" readonly class="w-full rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 px-3 py-2 text-gray-500 cursor-not-allowed">
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition">
                                {{ $editingVacationId ? 'Сохранить' : 'Добавить' }}
                            </button>
                            <button type="button" wire:click="cancelEdit" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-lg transition">Отмена</button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>
