<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4 bg-white dark:bg-gray-900 p-4 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
        <div class="flex items-center gap-4">
            <h2 class="text-lg font-bold">Мониторинг загрузки групп ({{ count($this->groups) }})</h2>
            <div class="flex items-center gap-2">
                <input type="date" wire:model="dateFrom" class="text-sm rounded-lg border-gray-300 dark:bg-gray-800 dark:border-gray-700">
                <span class="text-gray-400">—</span>
                <input type="date" wire:model="dateTo" class="text-sm rounded-lg border-gray-300 dark:bg-gray-800 dark:border-gray-700">
            </div>
        </div>
        
        <div class="flex items-center gap-4 text-xs">
            <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-emerald-500"></span> 3-5 пар</div>
            <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-amber-400"></span> 1-2 пары</div>
            <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-red-100 dark:bg-red-900/30 border border-red-200"></span> Нет пар</div>
            <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-blue-100 dark:bg-blue-900/30 border border-blue-200"></span> Практика</div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800/50">
                        <th class="p-3 border-b border-r border-gray-200 dark:border-gray-700 sticky left-0 z-10 bg-gray-50 dark:bg-gray-800">Группа</th>
                        @foreach($this->dates as $date)
                            <th class="p-3 border-b border-gray-200 dark:border-gray-700 text-center min-w-[100px]">
                                <div class="capitalize-first">
                                    {{ \Carbon\Carbon::parse($date)->translatedFormat('D') }}
                                </div>
                                <div class="text-[10px] text-gray-500 font-normal">
                                    {{ \Carbon\Carbon::parse($date)->format('d.m') }}
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($this->groups as $group)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors">
                            <td class="p-3 border-b border-r border-gray-200 dark:border-gray-700 font-medium sticky left-0 z-10 bg-white dark:bg-gray-900">
                                {{ $group->name }}
                            </td>
                            @foreach($this->dates as $date)
                                @php $cell = $this->matrix[$group->id][$date]; @endphp
                                <td class="p-2 border-b border-gray-200 dark:border-gray-700 text-center">
                                    <div class="relative group cursor-help">
                                        @if($cell['is_day_off'] && $cell['count'] === 0)
                                            <div class="w-full py-1.5 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-500 text-[10px] font-medium uppercase">
                                                Выходной
                                            </div>
                                        @elseif($cell['is_practice'])
                                            <div class="w-full py-1.5 rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-[10px] font-bold uppercase">
                                                Практика
                                            </div>
                                        @elseif($cell['count'] > 0)
                                            <div @class([
                                                'w-full py-1.5 rounded-lg font-bold',
                                                'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300' => $cell['status'] === 'normal' || $cell['status'] === 'high',
                                                'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300' => $cell['status'] === 'low',
                                            ])>
                                                {{ $cell['count'] }}
                                            </div>
                                        @else
                                            <div class="w-full py-1.5 rounded-lg bg-red-50 dark:bg-red-900/10 border border-red-100 dark:border-red-900/30 text-red-400">
                                                —
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
