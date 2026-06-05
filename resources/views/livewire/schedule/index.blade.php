<div class="space-y-6" x-data>
    @if (session('message'))
        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 rounded-lg text-sm text-emerald-700 dark:text-emerald-300">
            {{ session('message') }}
        </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Расписание</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Версии расписания и управление</p>
        </div>
        <div class="mt-4 sm:mt-0 flex items-center gap-2">
            @if(!empty($selectedVersions))
                <button wire:click="deleteSelected" wire:confirm="Удалить выбранные версии ({{ count($selectedVersions) }})? Это действие нельзя отменить." class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm font-medium transition-colors">
                    Удалить выбранные ({{ count($selectedVersions) }})
                </button>
            @endif
            <a href="{{ route('schedule.generate') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium transition-colors">
                Генерация
            </a>
            <button wire:click="create" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium transition-colors">
                + Создать расписание
            </button>
        </div>
    </div>

    <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="p-4 border-b border-gray-100 dark:border-gray-700">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1">
                    <input type="text" wire:model.live="search" placeholder="Поиск по названию..."
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 px-4 py-2 text-sm text-gray-900 dark:text-gray-200 placeholder-gray-400 dark:placeholder-gray-500 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition-colors">
                </div>
                <select wire:model.live="statusFilter"
                    class="rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 px-4 py-2 text-sm text-gray-900 dark:text-gray-200 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 outline-none transition-colors">
                    <option value="">Все статусы</option>
                    <option value="draft">Черновик</option>
                    <option value="published">Опубликовано</option>
                    <option value="archived">Архив</option>
                </select>
                @if($search || $statusFilter)
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
                        <th class="px-6 py-3 text-left">
                            <input type="checkbox" wire:model.live="selectAll" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                        </th>
                        <th class="text-left px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Название</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Отделение</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Учебный год</th>
                        <th class="text-left px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Период</th>
                        <th class="text-center px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Статус</th>
                        <th class="text-center px-6 py-3 font-medium text-gray-500 dark:text-gray-400">Действия</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($versions as $version)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors {{ in_array($version->id, $selectedVersions) ? 'bg-emerald-50/30 dark:bg-emerald-900/10' : '' }}">
                            <td class="px-6 py-4">
                                <input type="checkbox" wire:model.live="selectedVersions" value="{{ $version->id }}" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-cyan-50 dark:bg-cyan-900/30 flex items-center justify-center text-sm font-bold text-cyan-600 dark:text-cyan-400">Р</div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $version->name }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-700 dark:text-gray-300">{{ $version->department?->short_name ?? '—' }}</td>
                            <td class="px-6 py-4 text-gray-700 dark:text-gray-300">{{ $version->academicYear?->name ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <span class="text-gray-700 dark:text-gray-300 text-xs">
                                    {{ $version->date_from?->format('d.m.Y') ?? '—' }} — {{ $version->date_to?->format('d.m.Y') ?? '—' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @php
                                    $statusClasses = [
                                        'draft' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                        'published' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                                        'archived' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                    ];
                                    $statusLabels = [
                                        'draft' => 'Черновик',
                                        'published' => 'Опубликовано',
                                        'archived' => 'Архив',
                                    ];
                                    $class = $statusClasses[$version->status] ?? 'bg-gray-100 text-gray-600';
                                    $label = $statusLabels[$version->status] ?? $version->status;
                                @endphp
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium {{ $class }}">
                                    {{ $label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('schedule.view', $version) }}" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" title="Просмотр">
                                        Смотр.
                                    </a>
                                    <button wire:click="publish({{ $version->id }})" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-xs" title="Опубликовать">
                                        Публ.
                                    </button>
                                    <button wire:click="openExportModal({{ $version->id }})" class="p-1.5 rounded-lg text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors text-xs" title="Экспорт расписания">
                                        Экспорт
                                    </button>
                                    @if($version->status !== 'archived')
                                        <button wire:click="archive({{ $version->id }})" class="p-1.5 rounded-lg text-amber-400 hover:text-amber-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-xs" title="В архив">
                                            Архив
                                        </button>
                                    @endif
                                    <button wire:click="deleteVersion({{ $version->id }})" wire:confirm="Удалить расписание? Все данные будут потеряны." class="p-1.5 rounded-lg text-red-400 hover:text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors text-xs" title="Удалить">
                                        Удал.
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <p class="text-gray-400 dark:text-gray-500">Версии расписания не найдены</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($versions->hasPages())
            <div class="px-6 py-3 border-t border-gray-100 dark:border-gray-700">
                {{ $versions->links() }}
            </div>
        @endif
    </div>

    {{-- Export Date Picker Modal --}}
    @if($showExportModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm"
            wire:click.self="closeExportModal"
        >
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden">

                {{-- Modal Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-indigo-50 dark:bg-indigo-900/30">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Экспорт расписания</h3>
                        @if($this->exportVersion)
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ $this->exportVersion->name }}
                                &nbsp;·&nbsp;
                                {{ $this->exportVersion->date_from?->format('d.m.Y') }} — {{ $this->exportVersion->date_to?->format('d.m.Y') }}
                            </p>
                        @endif
                    </div>
                    <button wire:click="closeExportModal" class="p-2 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="px-6 py-5">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Выберите дату, на которую нужно сформировать расписание по кафедрам.
                        Формат: <strong>1 лист = 1 кафедра</strong>, группы расположены в 3 колонки.
                    </p>

                    @php $dates = $this->availableExportDates; @endphp

                    @if(empty($dates))
                        <div class="text-center py-8 text-gray-400 dark:text-gray-500">
                            <svg class="w-10 h-10 mx-auto mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <p class="text-sm">Нет доступных дат в данной версии</p>
                        </div>
                    @else
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-72 overflow-y-auto pr-1">
                            @foreach($dates as $date)
                                <a
                                    href="{{ route('schedule.export.download', [$exportVersionId, $date['value']]) }}"
                                    target="_blank"
                                    class="group flex flex-col items-center justify-center gap-1 px-3 py-3 rounded-xl border text-center text-sm transition-all
                                        {{ $date['hasLessons']
                                            ? 'border-indigo-200 bg-indigo-50 dark:bg-indigo-900/20 dark:border-indigo-700 hover:bg-indigo-100 dark:hover:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300'
                                            : 'border-gray-200 bg-gray-50 dark:bg-gray-700/50 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400' }}"
                                    title="{{ $date['hasLessons'] ? 'Есть занятия' : 'Занятий нет' }}"
                                >
                                    <span class="font-semibold text-xs uppercase tracking-wide opacity-70">
                                        {{ $date['dayLabel'] }}
                                    </span>
                                    <span class="font-bold text-base leading-tight">
                                        {{ $date['dateLabel'] }}
                                    </span>
                                    @if($date['hasLessons'])
                                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 mt-0.5"></span>
                                    @endif
                                </a>
                            @endforeach
                        </div>

                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-3">
                            <span class="inline-flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                                Дни с занятиями выделены синим
                            </span>
                        </p>
                    @endif
                </div>

                {{-- Modal Footer --}}
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex justify-end">
                    <button wire:click="closeExportModal" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors">
                        Закрыть
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
