<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold">
            @if($selectedTeacherId)
                <button wire:click="backToList" class="text-sm text-emerald-600 hover:text-emerald-700 mb-1 inline-block">← Назад к списку</button>
                <br>
                Детальная нагрузка
                @if($academicYearId)
                    @php $ay = $this->academicYears->firstWhere('id', $academicYearId); @endphp
                    <span class="text-base font-normal text-gray-500">— {{ $ay?->name ?? '' }}</span>
                @endif
            @else
                Нагрузка преподавателей
            @endif
        </h2>
        <button wire:click="exportExcel" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition">
            Экспорт Excel
        </button>
    </div>

    @if(!$selectedTeacherId)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Кафедра</label>
                <select wire:model.live="departmentId" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                    <option value="">Все кафедры</option>
                    @foreach ($this->departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Учебный год</label>
                <select wire:model.live="academicYearId" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2">
                    <option value="">Все годы</option>
                    @foreach ($this->academicYears as $year)
                        <option value="{{ $year->id }}">{{ $year->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-900/50">
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Преподаватель</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Должность</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Кафедра</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Дисциплин</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">План (часы)</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">По семестрам</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Проведено</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500">Ставка</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($workloadData as $data)
                        <tr wire:click="selectTeacher({{ $data['id'] }})" class="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-4 py-3 font-medium">{{ $data['name'] }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ $data['position'] ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $data['department'] ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $data['disciplines_count'] }}</td>
                            <td class="px-4 py-3">{{ $data['planned_hours'] }}</td>
                            <td class="px-4 py-3">{{ $data['semester_hours'] }}</td>
                            <td class="px-4 py-3">{{ $data['conducted_lessons'] }}</td>
                            <td class="px-4 py-3">{{ $data['rate'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">Нет данных</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        @if($teacherDetail)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 pb-4 border-b border-gray-200 dark:border-gray-700">
                    <div>
                        <div class="text-xs text-gray-500 uppercase">Преподаватель</div>
                        <div class="font-semibold mt-1 text-lg">{{ $teacherDetail['teacher']->full_name }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 uppercase">Должность</div>
                        <div class="mt-1">{{ $teacherDetail['teacher']->position?->name ?? '—' }}</div>
                        <div class="text-xs text-gray-400">{{ $teacherDetail['teacher']->department?->name ?? '' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 uppercase">Ставка</div>
                        <div class="mt-1">{{ $teacherDetail['teacher']->rate }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="border border-emerald-200 dark:border-emerald-800 rounded-xl p-4 bg-emerald-50/30 dark:bg-emerald-900/10">
                        <h3 class="font-semibold text-emerald-700 dark:text-emerald-300 mb-3">1 семестр (сентябрь — декабрь)</h3>
                        @if($teacherDetail['semester1']->isNotEmpty())
                            <div class="space-y-2">
                                @foreach($teacherDetail['semester1'] as $item)
                                    <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-emerald-100 dark:border-emerald-900/30">
                                        <div class="flex items-start justify-between">
                                            <div>
                                                <div class="font-medium text-sm">{{ $item['discipline_name'] }}</div>
                                                <div class="text-xs text-gray-500">{{ $item['group_name'] }}</div>
                                            </div>
                                            @if($item['control_form'])
                                                <span class="px-2 py-0.5 rounded text-xs font-medium whitespace-nowrap {{ $item['is_exam'] ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300' }}">
                                                    {{ $item['control_form'] }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="mt-2 flex items-center gap-3 text-xs">
                                            <span class="text-gray-500">План: <strong>{{ $item['planned'] }}</strong> ч.</span>
                                            <span class="text-emerald-600">Проведено: <strong>{{ $item['conducted'] }}</strong> ч.</span>
                                            <span class="{{ $item['remaining'] > 0 ? 'text-amber-600' : 'text-green-600' }}">
                                                Осталось: <strong>{{ $item['remaining'] }}</strong> ч.
                                            </span>
                                        </div>
                                        <div class="mt-1.5 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                            @php $pct = $item['planned'] > 0 ? min(100, ($item['conducted'] / $item['planned']) * 100) : 0; @endphp
                                            <div class="bg-emerald-500 h-1.5 rounded-full transition-all" style="width: {{ $pct }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-3 flex justify-between text-sm font-medium px-1">
                                <span>Итого: <strong>{{ $teacherDetail['semester1_total'] }}</strong> ч.</span>
                                <span class="text-emerald-600">Проведено: <strong>{{ $teacherDetail['semester1_conducted'] }}</strong> ч.</span>
                            </div>
                        @else
                            <p class="text-sm text-gray-400">Нет дисциплин в 1 семестре</p>
                        @endif
                    </div>

                    <div class="border border-amber-200 dark:border-amber-800 rounded-xl p-4 bg-amber-50/30 dark:bg-amber-900/10">
                        <h3 class="font-semibold text-amber-700 dark:text-amber-300 mb-3">2 семестр (январь — июнь)</h3>
                        @if($teacherDetail['semester2']->isNotEmpty())
                            <div class="space-y-2">
                                @foreach($teacherDetail['semester2'] as $item)
                                    <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-amber-100 dark:border-amber-900/30">
                                        <div class="flex items-start justify-between">
                                            <div>
                                                <div class="font-medium text-sm">{{ $item['discipline_name'] }}</div>
                                                <div class="text-xs text-gray-500">{{ $item['group_name'] }}</div>
                                            </div>
                                            @if($item['control_form'])
                                                <span class="px-2 py-0.5 rounded text-xs font-medium whitespace-nowrap {{ $item['is_exam'] ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300' }}">
                                                    {{ $item['control_form'] }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="mt-2 flex items-center gap-3 text-xs">
                                            <span class="text-gray-500">План: <strong>{{ $item['planned'] }}</strong> ч.</span>
                                            <span class="text-emerald-600">Проведено: <strong>{{ $item['conducted'] }}</strong> ч.</span>
                                            <span class="{{ $item['remaining'] > 0 ? 'text-amber-600' : 'text-green-600' }}">
                                                Осталось: <strong>{{ $item['remaining'] }}</strong> ч.
                                            </span>
                                        </div>
                                        <div class="mt-1.5 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                            @php $pct = $item['planned'] > 0 ? min(100, ($item['conducted'] / $item['planned']) * 100) : 0; @endphp
                                            <div class="bg-amber-500 h-1.5 rounded-full transition-all" style="width: {{ $pct }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-3 flex justify-between text-sm font-medium px-1">
                                <span>Итого: <strong>{{ $teacherDetail['semester2_total'] }}</strong> ч.</span>
                                <span class="text-emerald-600">Проведено: <strong>{{ $teacherDetail['semester2_conducted'] }}</strong> ч.</span>
                            </div>
                        @else
                            <p class="text-sm text-gray-400">Нет дисциплин во 2 семестре</p>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
