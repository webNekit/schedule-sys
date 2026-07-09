<div class="space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold">Авторские правила</h2>
            <p class="text-sm text-gray-500">Конструктор собственных правил. Жёсткие правила «запрет» и «привязка» учитываются прямо при генерации; остальные подсвечиваются при проверке.</p>
        </div>
        <div class="shrink-0 flex items-center gap-2">
            <a href="{{ asset('docs/custom-rules-guide.pdf') }}" target="_blank" rel="noopener"
                class="inline-flex items-center gap-1.5 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg text-sm font-medium transition">
                📄 Инструкция
            </a>
            <button wire:click="newRule"
                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium transition">
                + Новое правило
            </button>
        </div>
    </div>

    @if (session('message'))
        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-lg text-sm text-emerald-700 dark:text-emerald-300">
            {{ session('message') }}
        </div>
    @endif

    {{-- ===== Список правил ===== --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-900/40 text-gray-500">
                <tr>
                    <th class="px-4 py-2.5 text-left font-medium">Название</th>
                    <th class="px-4 py-2.5 text-left font-medium">Тип</th>
                    <th class="px-4 py-2.5 text-left font-medium">Уровень</th>
                    <th class="px-4 py-2.5 text-left font-medium">Строгость</th>
                    <th class="px-4 py-2.5 text-center font-medium">Вкл.</th>
                    <th class="px-4 py-2.5 text-right font-medium">Действия</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($rules as $rule)
                    @php $typeLabels = ['forbid' => 'Запрет', 'require' => 'Привязка', 'limit' => 'Лимит']; @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30">
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $rule->name }}</div>
                            @if ($rule->description)
                                <div class="text-xs text-gray-500 mt-0.5">{{ $rule->description }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded text-xs bg-gray-100 dark:bg-gray-700">{{ $typeLabels[$rule->definition['type'] ?? ''] ?? ($rule->definition['type'] ?? '—') }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                            @if ($rule->scope === 'global') Глобально
                            @elseif ($rule->scope === 'course') Курс {{ $rule->scope_id }}
                            @else Группа #{{ $rule->scope_id }}
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded text-xs {{ $rule->severity === 'hard' ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' }}">
                                {{ $rule->severity === 'hard' ? 'Ошибка' : 'Предупреждение' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button wire:click="toggle({{ $rule->id }})"
                                class="inline-flex h-5 w-9 items-center rounded-full transition {{ $rule->is_enabled ? 'bg-emerald-500' : 'bg-gray-300 dark:bg-gray-600' }}">
                                <span class="h-4 w-4 transform rounded-full bg-white transition {{ $rule->is_enabled ? 'translate-x-4' : 'translate-x-1' }}"></span>
                            </button>
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <button wire:click="edit({{ $rule->id }})" class="text-sm text-blue-600 hover:underline">Изменить</button>
                            <button wire:click="delete({{ $rule->id }})" wire:confirm="Удалить правило?" class="ml-3 text-sm text-red-600 hover:underline">Удалить</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-gray-400">Пока нет авторских правил. Нажмите «Новое правило».</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ===== Форма ===== --}}
    @if ($showForm)
        <div class="fixed inset-0 bg-black/50 flex items-start justify-center z-50 overflow-y-auto py-8" wire:click.self="cancel">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-4xl mx-4">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <h3 class="text-lg font-semibold">{{ $editingId ? 'Редактирование правила' : 'Новое правило' }}</h3>
                        <a href="{{ asset('docs/custom-rules-guide.pdf') }}" target="_blank" rel="noopener"
                            class="inline-flex items-center gap-1 text-sm text-emerald-600 dark:text-emerald-400 hover:underline">
                            📄 Как составить правило?
                        </a>
                    </div>
                    <button wire:click="cancel" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
                </div>

                <div class="p-6 space-y-5">
                    {{-- Название / описание --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Название</label>
                            <input type="text" wire:model="name"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-3 py-2 text-sm">
                            @error('name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Описание (необязательно)</label>
                            <input type="text" wire:model="description"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-3 py-2 text-sm">
                        </div>
                    </div>

                    {{-- Уровень / строгость --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Уровень</label>
                            <select wire:model.live="scope" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-3 py-2 text-sm">
                                <option value="global">Глобально</option>
                                <option value="course">По курсу</option>
                                <option value="group">По группе</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">{{ $scope === 'course' ? 'Курс' : ($scope === 'group' ? 'Группа' : 'Применимость') }}</label>
                            @if ($scope === 'course')
                                <select wire:model="scopeId" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-3 py-2 text-sm">
                                    @foreach ([1, 2, 3, 4] as $c) <option value="{{ $c }}">{{ $c }} курс</option> @endforeach
                                </select>
                            @elseif ($scope === 'group')
                                <select wire:model="scopeId" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-3 py-2 text-sm">
                                    @foreach ($groups as $g) <option value="{{ $g->id }}">{{ $g->name }}</option> @endforeach
                                </select>
                            @else
                                <input type="text" value="Весь колледж" disabled
                                    class="w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 px-3 py-2 text-sm text-gray-400">
                            @endif
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Строгость</label>
                            <select wire:model="severity" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-3 py-2 text-sm">
                                <option value="soft">Предупреждение</option>
                                <option value="hard">Ошибка (учитывается при генерации)</option>
                            </select>
                        </div>
                    </div>

                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:model="isEnabled" class="rounded border-gray-300 text-emerald-600"> Включено
                    </label>

                    {{-- Тип правила --}}
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Тип правила</label>
                        <div class="inline-flex rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden text-sm">
                            @foreach (['forbid' => 'Запрет', 'require' => 'Привязка', 'limit' => 'Лимит'] as $tVal => $tLbl)
                                <button type="button" wire:click="setType('{{ $tVal }}')"
                                    class="px-4 py-2 transition {{ $type === $tVal ? 'bg-emerald-600 text-white' : 'bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                                    {{ $tLbl }}
                                </button>
                            @endforeach
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            @switch($type)
                                @case('forbid') Запрещает пары, подходящие под условия ниже. @break
                                @case('require') Пары из «для каких пар» обязаны удовлетворять «обязательно». @break
                                @case('limit') Считает пары по выбранным полям и сверяет с порогом. @break
                            @endswitch
                        </p>
                    </div>

                    @error('definition') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

                    @if ($advanced)
                        {{-- Расширенный режим: сырой JSON --}}
                        <div>
                            <label class="block text-sm font-medium mb-1">Определение (JSON)</label>
                            <textarea wire:model="definitionJson" rows="12" spellcheck="false"
                                class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-3 py-2 font-mono text-xs leading-relaxed"></textarea>
                            <p class="text-xs text-gray-500 mt-1">Поля: {{ implode(', ', $fields) }}. Условия: all / any / not / {field, op, value}.</p>
                        </div>
                    @else
                        {{-- Визуальный конструктор --}}
                        <div class="space-y-3 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-semibold">{{ $type === 'limit' ? 'Считать пары, где' : 'Условия (для каких пар)' }}</span>
                                @if (count($matchConditions) > 1)
                                    <select wire:model.live="matchCombinator" class="rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-2 py-1 text-xs">
                                        <option value="all">Все условия (И)</option>
                                        <option value="any">Любое из (ИЛИ)</option>
                                    </select>
                                @endif
                            </div>

                            @foreach ($matchConditions as $i => $row)
                                @include('livewire.admin.partials.condition-row', ['prop' => 'matchConditions', 'i' => $i, 'row' => $row, 'removeMethod' => 'removeMatchCondition'])
                            @endforeach

                            <button type="button" wire:click="addMatchCondition" class="text-sm text-emerald-600 hover:underline">+ условие</button>
                        </div>

                        @if ($type === 'require')
                            <div class="space-y-3 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-semibold">Обязательно должно выполняться</span>
                                    @if (count($requireConditions) > 1)
                                        <select wire:model.live="requireCombinator" class="rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-2 py-1 text-xs">
                                            <option value="all">Все условия (И)</option>
                                            <option value="any">Любое из (ИЛИ)</option>
                                        </select>
                                    @endif
                                </div>

                                @foreach ($requireConditions as $i => $row)
                                    @include('livewire.admin.partials.condition-row', ['prop' => 'requireConditions', 'i' => $i, 'row' => $row, 'removeMethod' => 'removeRequireCondition'])
                                @endforeach

                                <button type="button" wire:click="addRequireCondition" class="text-sm text-emerald-600 hover:underline">+ условие</button>
                            </div>
                        @endif

                        @if ($type === 'limit')
                            <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 space-y-3">
                                <div>
                                    <span class="text-sm font-semibold">Группировать по</span>
                                    <div class="flex flex-wrap gap-3 mt-2">
                                        @foreach ($fields as $fName)
                                            <label class="inline-flex items-center gap-1.5 text-xs">
                                                <input type="checkbox" value="{{ $fName }}" wire:model="limitGroupBy" class="rounded border-gray-300 text-emerald-600">
                                                {{ $fieldLabels[$fName] ?? $fName }}
                                            </label>
                                        @endforeach
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">Напр.: «преподаватель + дата» = считать пары преподавателя за день.</p>
                                </div>
                                <div class="flex items-center gap-2 text-sm">
                                    <span>Число пар</span>
                                    <select wire:model="limitOp" class="rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-2 py-1.5 text-sm">
                                        <option value="<=">не больше (≤)</option>
                                        <option value=">=">не меньше (≥)</option>
                                        <option value="<">меньше (&lt;)</option>
                                        <option value=">">больше (&gt;)</option>
                                        <option value="=">ровно (=)</option>
                                    </select>
                                    <input type="number" wire:model="limitValue" class="w-24 rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-2 py-1.5 text-sm">
                                </div>
                            </div>
                        @endif
                    @endif

                    {{-- Переключатель расширенного режима --}}
                    <label class="inline-flex items-center gap-2 text-xs text-gray-500">
                        <input type="checkbox" wire:model.live="advanced" class="rounded border-gray-300 text-emerald-600">
                        Расширенный режим (JSON) — для вложенной логики И/ИЛИ/НЕ
                    </label>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-2">
                    <button wire:click="cancel" class="px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">Отмена</button>
                    <button wire:click="save" class="px-4 py-2 text-sm rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-medium">Сохранить</button>
                </div>
            </div>
        </div>
    @endif
</div>
