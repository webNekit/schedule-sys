<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-bold">Правила генерации расписания</h2>
        <p class="text-sm text-gray-500">Параметры и ограничения генератора. Можно задать глобально, по курсу или для конкретной группы — более специфичный уровень побеждает.</p>
    </div>

    @if (session('message'))
        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-lg text-sm text-emerald-700 dark:text-emerald-300">
            {{ session('message') }}
        </div>
    @endif

    {{-- Краткая инструкция --}}
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-5 text-sm text-blue-900 dark:text-blue-200 space-y-2">
        <p class="font-semibold">Как пользоваться</p>
        <ol class="list-decimal list-inside space-y-1 text-blue-800 dark:text-blue-300">
            <li>Выберите <b>уровень</b>: «Глобально» — для всего колледжа; «По курсу» или «По группе» — чтобы переопределить правило только для них.</li>
            <li><b>Ограничения</b> — это запреты/требования. Галочка «Включено» включает проверку. «Ошибка» делает нарушение критичным (красным), «Предупреждение» — некритичным (жёлтым).</li>
            <li><b>Параметры генерации</b> — числа и списки, по которым строится расписание (сколько пар в день, как выбираются дисциплины и т.д.).</li>
            <li>После изменения строки нажмите <b>«Сохранить»</b>. На уровне курса/группы появится кнопка «Сбросить» — она вернёт значение к глобальному.</li>
        </ol>
    </div>

    {{-- Подробная инструкция по всем полям --}}
    <div x-data="{ open: false }" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <button type="button" @click="open = !open"
            class="w-full flex items-center justify-between px-6 py-4 text-left">
            <span class="font-semibold text-lg">📖 Подробная инструкция по всем полям</span>
            <svg class="w-5 h-5 opacity-60 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>

        <div x-show="open" x-cloak x-transition.opacity class="px-6 pb-6 space-y-6 border-t border-gray-100 dark:border-gray-700 pt-5">
            <div>
                <h4 class="font-semibold text-sm uppercase tracking-wide text-gray-500 mb-3">Ограничения</h4>
                <div class="space-y-4">
                    @foreach ($guide['constraints'] as $item)
                        <div class="border-l-2 border-emerald-400 pl-4">
                            <p class="font-medium text-sm">{{ $item['title'] }}</p>
                            @if ($item['what'])
                                <p class="text-sm text-gray-600 dark:text-gray-300 mt-0.5">{{ $item['what'] }}</p>
                            @endif
                            @if ($item['how'])
                                <p class="text-sm text-gray-500 mt-1"><span class="font-medium text-gray-600 dark:text-gray-400">Как заполнять:</span> {{ $item['how'] }}</p>
                            @endif
                            @if (! empty($item['fields']))
                                <ul class="mt-1.5 space-y-0.5 text-sm text-gray-500">
                                    @foreach ($item['fields'] as $fName => $fText)
                                        <li>• <span class="font-medium text-gray-600 dark:text-gray-400">{{ $paramLabels[$fName] ?? $fName }}:</span> {{ $fText }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div>
                <h4 class="font-semibold text-sm uppercase tracking-wide text-gray-500 mb-3">Параметры генерации</h4>
                <div class="space-y-4">
                    @foreach ($guide['params'] as $item)
                        <div class="border-l-2 border-indigo-400 pl-4">
                            <p class="font-medium text-sm">{{ $item['title'] }}</p>
                            @if ($item['what'])
                                <p class="text-sm text-gray-600 dark:text-gray-300 mt-0.5">{{ $item['what'] }}</p>
                            @endif
                            @if ($item['how'])
                                <p class="text-sm text-gray-500 mt-1"><span class="font-medium text-gray-600 dark:text-gray-400">Как заполнять:</span> {{ $item['how'] }}</p>
                            @endif
                            @if (! empty($item['fields']))
                                <ul class="mt-1.5 space-y-0.5 text-sm text-gray-500">
                                    @foreach ($item['fields'] as $fName => $fText)
                                        <li>• <span class="font-medium text-gray-600 dark:text-gray-400">{{ $paramLabels[$fName] ?? $fName }}:</span> {{ $fText }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Scope selector --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <div class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Уровень</label>
                <select wire:model.live="scope" class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                    <option value="global">Глобально (весь колледж)</option>
                    <option value="course">По курсу</option>
                    <option value="group">По группе</option>
                </select>
            </div>

            @if ($scope === 'course')
                <div>
                    <label class="block text-sm font-medium mb-1">Курс</label>
                    <select wire:model.live="scopeId" class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                        @foreach ([1, 2, 3, 4] as $c)
                            <option value="{{ $c }}">{{ $c }} курс</option>
                        @endforeach
                    </select>
                </div>
            @elseif ($scope === 'group')
                <div>
                    <label class="block text-sm font-medium mb-1">Группа</label>
                    <select wire:model.live="scopeId" class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                        @foreach ($groups as $g)
                            <option value="{{ $g->id }}">{{ $g->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if ($scope !== 'global')
                <p class="text-xs text-gray-400 max-w-sm">Здесь задаются только переопределения. Незаданные правила наследуются с глобального уровня.</p>
            @endif
        </div>
    </div>

    @php
        $defaults = \App\Services\Schedule\SchedulingRuleResolver::DEFAULTS;
        $paramMeta = function ($key, $pName) use ($defaults) {
            $pDefault = $defaults[$key]['params'][$pName] ?? null;
            return ['isArray' => is_array($pDefault), 'isBool' => is_bool($pDefault), 'isInt' => is_int($pDefault)];
        };
    @endphp

    {{-- Constraints --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="font-semibold text-lg mb-1">Ограничения</h3>
        <p class="text-sm text-gray-500 mb-4">Запреты и требования, которые проверяются после генерации.</p>
        <div class="space-y-3">
            @foreach ($constraintKeys as $key)
                @php $row = $rows[$key]; @endphp
                <div class="flex flex-wrap items-center gap-3 border border-gray-100 dark:border-gray-700 rounded-lg p-3">
                    <div class="flex-1 min-w-[240px]">
                        <span class="text-sm font-medium">{{ $labels[$key] ?? $key }}</span>
                        @if ($scope !== 'global' && $row['overridden'])
                            <span class="ml-2 text-xs px-1.5 py-0.5 rounded bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">переопределено</span>
                        @endif
                        @if (! empty($descriptions[$key]))
                            <p class="text-xs text-gray-500 mt-0.5">{{ $descriptions[$key] }}</p>
                        @endif
                    </div>

                    <label class="flex items-center gap-1.5 text-sm">
                        <input type="checkbox" wire:model="rows.{{ $key }}.is_enabled" class="rounded border-gray-300 text-emerald-600">
                        Включено
                    </label>

                    <select wire:model="rows.{{ $key }}.severity" class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm">
                        <option value="hard">Ошибка (критично)</option>
                        <option value="soft">Предупреждение</option>
                    </select>

                    {{-- Числовые параметры ограничения (мин. пары / макс. часы) --}}
                    @foreach ($row['params'] as $pName => $pValue)
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs text-gray-500">{{ $paramLabels[$pName] ?? $pName }}</span>
                            <input type="number" wire:model="rows.{{ $key }}.params.{{ $pName }}" class="w-20 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm">
                        </div>
                    @endforeach

                    <button wire:click="saveRule('{{ $key }}')" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm transition">Сохранить</button>
                    @if ($scope !== 'global' && $row['overridden'])
                        <button wire:click="resetRule('{{ $key }}')" class="px-3 py-1.5 text-sm text-gray-500 hover:text-red-500 transition">Сбросить</button>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Parameters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h3 class="font-semibold text-lg mb-1">Параметры генерации</h3>
        <p class="text-sm text-gray-500 mb-4">Значения, по которым строится расписание.</p>
        <div class="space-y-3">
            @foreach ($paramKeys as $key)
                @php $row = $rows[$key]; @endphp
                <div class="flex flex-wrap items-center gap-3 border border-gray-100 dark:border-gray-700 rounded-lg p-3">
                    <div class="flex-1 min-w-[240px]">
                        <span class="text-sm font-medium">{{ $labels[$key] ?? $key }}</span>
                        @if ($scope !== 'global' && $row['overridden'])
                            <span class="ml-2 text-xs px-1.5 py-0.5 rounded bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">переопределено</span>
                        @endif
                        @if (! empty($descriptions[$key]))
                            <p class="text-xs text-gray-500 mt-0.5">{{ $descriptions[$key] }}</p>
                        @endif
                    </div>

                    @if ($key === 'min_lessons_check_weekdays')
                        {{-- Дни недели чекбоксами: галочка = в этот день минимум пар проверяется. Под днём — с какой по какую пару положено по курсам (из «Настроек системы»). --}}
                        <div class="w-full">
                            <p class="text-xs text-gray-400 mb-2">Галочка = в этот день минимум пар проверяется. Под днём — с какой по какую пару по курсам (1/2/3/4).</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($dayLabels as $dayNum => $dayLabel)
                                    @php $isChecked = in_array($dayNum, (array) $row['params']['days'], true); @endphp
                                    <label class="flex flex-col items-center gap-1 px-3 py-2 rounded-lg border text-xs cursor-pointer transition
                                        {{ $isChecked ? 'bg-emerald-50 border-emerald-300 dark:bg-emerald-900/30 dark:border-emerald-700' : 'bg-gray-50 border-gray-200 dark:bg-gray-900 dark:border-gray-700' }}">
                                        <span class="flex items-center gap-1.5 font-medium">
                                            <input type="checkbox" value="{{ $dayNum }}" wire:model="rows.{{ $key }}.params.days" class="rounded border-gray-300 text-emerald-600">
                                            {{ $dayLabel }}
                                        </span>
                                        <span class="text-[10px] text-gray-400 leading-tight text-center space-y-0.5">
                                            @foreach ([1, 2, 3, 4] as $course)
                                                @php $r = $dayPairs[$dayNum][$course] ?? ['from' => 0, 'to' => 0, 'count' => 0]; @endphp
                                                <span class="block">{{ $course }}к: {{ $r['count'] > 0 ? $r['from'].'–'.$r['to'].' пара' : '—' }}</span>
                                            @endforeach
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="flex flex-wrap items-center gap-3">
                            @foreach ($row['params'] as $pName => $pValue)
                                @php $meta = $paramMeta($key, $pName); @endphp
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs text-gray-500">{{ $paramLabels[$pName] ?? $pName }}</span>
                                    @if ($key === 'building_rotation' && $pName === 'strategy')
                                        <select wire:model="rows.{{ $key }}.params.{{ $pName }}" class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm">
                                            <option value="by_weekday">Чередовать по дням недели</option>
                                            <option value="primary_only">Только основной корпус</option>
                                        </select>
                                    @elseif ($meta['isBool'])
                                        <input type="checkbox" wire:model="rows.{{ $key }}.params.{{ $pName }}" class="rounded border-gray-300 text-emerald-600">
                                    @elseif ($meta['isInt'])
                                        <input type="number" wire:model="rows.{{ $key }}.params.{{ $pName }}" class="w-24 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm">
                                    @else
                                        <input type="text" wire:model="rows.{{ $key }}.params.{{ $pName }}" class="w-44 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-2 py-1.5 text-sm"
                                            @if ($meta['isArray']) placeholder="через запятую" @endif>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <button wire:click="saveRule('{{ $key }}')" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm transition">Сохранить</button>
                    @if ($scope !== 'global' && $row['overridden'])
                        <button wire:click="resetRule('{{ $key }}')" class="px-3 py-1.5 text-sm text-gray-500 hover:text-red-500 transition">Сбросить</button>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
