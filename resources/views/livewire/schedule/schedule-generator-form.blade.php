<div class="space-y-6">
    <h2 class="text-2xl font-bold">Генерация расписания</h2>

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

    <form wire:submit="generate" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Тип периода <span class="text-red-500">*</span></label>
                <select wire:model.live="periodType" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                    <option value="day">День</option>
                    <option value="week">Неделя</option>
                    <option value="month">Месяц</option>
                    <option value="semester">Семестр (весь)</option>
                </select>
                <p class="text-xs text-gray-400 mt-1">Выберите из списка</p>
            </div>

            @if ($periodType === 'semester')
                <div>
                    <label class="block text-sm font-medium mb-1">Номер семестра <span class="text-red-500">*</span></label>
                    <div class="flex gap-3 mt-1">
                        <label class="flex items-center gap-2 cursor-pointer px-4 py-2 rounded-lg border transition
                            {{ $semester == 1 ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300' : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-gray-400' }}">
                            <input type="radio" wire:model.live="semester" value="1" class="accent-emerald-600">
                            <span class="text-sm font-medium">1 семестр</span>
                            <span class="text-xs opacity-60">(осень)</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer px-4 py-2 rounded-lg border transition
                            {{ $semester == 2 ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300' : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-gray-400' }}">
                            <input type="radio" wire:model.live="semester" value="2" class="accent-emerald-600">
                            <span class="text-sm font-medium">2 семестр</span>
                            <span class="text-xs opacity-60">(весна)</span>
                        </label>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Даты берутся из текущего учебного года</p>
                    @error('semester') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            @elseif ($periodType === 'day')
                <div>
                    <label class="block text-sm font-medium mb-1">Дата <span class="text-red-500">*</span></label>
                    <input type="date" wire:model="date" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                    <p class="text-xs text-gray-400 mt-1">Выберите дату</p>
                    @error('date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            @elseif ($periodType === 'week')
                <div>
                    <label class="block text-sm font-medium mb-1">Начало недели <span class="text-red-500">*</span></label>
                    <input type="date" wire:model="weekStart" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                    <p class="text-xs text-gray-400 mt-1">Выберите дату</p>
                    @error('weekStart') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            @elseif ($periodType === 'month')
                <div>
                    <label class="block text-sm font-medium mb-1">Месяц <span class="text-red-500">*</span></label>
                    <select wire:model="month" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                        @foreach(range(1, 12) as $m)
                            <option value="{{ $m }}">{{ Carbon\Carbon::create()->month($m)->translatedFormat('F') }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Выберите из списка</p>
                    @error('month') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Год <span class="text-red-500">*</span></label>
                    <input type="number" wire:model="year" min="2000" max="2100" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                    <p class="text-xs text-gray-400 mt-1">Введите число</p>
                    @error('year') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            @endif

            <div>
                <label class="block text-sm font-medium mb-1">Кафедра (фильтр)</label>
                <select wire:model.live="departmentId" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                    <option value="">Все кафедры</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">Выберите из списка</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" wire:model.live="allGroups" id="allGroups" class="rounded border-gray-300">
            <label for="allGroups" class="text-sm">Все группы</label>
        </div>

        @if (!$allGroups)
            <div>
                <label class="block text-sm font-medium mb-1">Группы <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2 max-h-48 overflow-y-auto">
                    @foreach ($groups as $group)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model="selectedGroups" value="{{ $group->id }}" class="rounded border-gray-300">
                            {{ $group->name }}
                        </label>
                    @endforeach
                </div>
                @error('selectedGroups') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        @endif

        <div class="flex gap-3">
            <button type="submit" wire:loading.attr="disabled" class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg transition disabled:opacity-50">
                @if ($generating)
                    Генерация...
                @else
                    Сгенерировать
                @endif
            </button>
            <button type="button" wire:click="resetForm" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-lg transition">
                Сбросить
            </button>
        </div>
    </form>

    @if ($generating)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                <div class="bg-emerald-500 h-4 rounded-full transition-all" style="width: {{ $progress }}%"></div>
            </div>
            <p class="text-sm text-gray-500 mt-2">Генерация... {{ $progress }}%</p>
        </div>
    @endif

    @if ($result)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            @if ($result['success'])
                <div class="space-y-3">
                    <div class="flex items-center gap-2 text-emerald-600">
                        <span>✓</span>
                        <span class="font-semibold">{{ $result['message'] }}</span>
                    </div>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                            <span class="text-gray-500">Всего занятий:</span>
                            <span class="font-bold ml-2">{{ $result['totalLessons'] }}</span>
                        </div>
                        <div class="p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                            <span class="text-gray-500">Конфликтов:</span>
                            <span class="font-bold ml-2 {{ $result['conflicts'] > 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ $result['conflicts'] }}</span>
                        </div>
                    </div>
                    @if (! empty($result['warnings']))
                        <div class="p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg text-sm text-amber-700 dark:text-amber-300 space-y-1">
                            <p class="font-semibold">Предупреждения ({{ count($result['warnings']) }}):</p>
                            <ul class="list-disc list-inside space-y-0.5 max-h-40 overflow-y-auto">
                                @foreach ($result['warnings'] as $warning)
                                    <li>{{ $warning }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="flex gap-3">
                        <button wire:click="publish" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition">
                            Опубликовать
                        </button>
                        <a href="{{ route('schedule.view', ['version' => $result['versionId'] ?? '']) }}" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-lg transition text-center">
                            Просмотр
                        </a>
                    </div>
                </div>
            @else
                <div class="flex items-center gap-2 text-red-600">
                    <span>✗</span>
                    <span class="font-semibold">{{ $result['message'] }}</span>
                </div>
            @endif
        </div>
    @endif
</div>
