<div class="relative" x-data="{ open: false }" @click.outside="open = false">
    {{-- Кнопка колокольчика --}}
    <button @click="open = !open"
        class="relative p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>

        {{-- Красная точка, если есть непрочитанные --}}
        @if($this->unreadCount > 0)
            <span
                class="absolute top-1 right-1.5 flex h-2.5 w-2.5 items-center justify-center rounded-full bg-red-500 ring-2 ring-white dark:ring-gray-900"></span>
        @endif
    </button>

    {{-- Выпадающее окно --}}
    <div x-show="open" x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute right-0 mt-2 w-80 sm:w-96 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-xl py-1 z-50"
        style="display: none;">

        {{-- Шапка --}}
        <div class="px-4 py-2 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <h3 class="font-semibold text-sm text-gray-900 dark:text-white">Уведомления</h3>
            @if($this->unreadCount > 0)
                <button wire:click="markAllAsRead"
                    class="text-xs font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 transition-colors">
                    Прочитать все
                </button>
            @endif
        </div>

        {{-- Список уведомлений --}}
        <div class="max-h-[70vh] overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700">
            @forelse($this->notifications as $notification)
                <div
                    class="px-4 py-3 transition-colors {{ $notification->is_read ? 'opacity-60 hover:bg-gray-50 dark:hover:bg-gray-700/50' : 'bg-blue-50/40 hover:bg-blue-50 dark:bg-blue-900/10 dark:hover:bg-blue-900/20' }}">
                    <div class="flex items-start gap-3">
                        {{-- Иконка по типу уведомления --}}
                        <div class="shrink-0 mt-0.5">
                            @if($notification->type === 'practice_reminder')
                                <div
                                    class="w-8 h-8 rounded-full bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400 flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            @elseif($notification->type === 'error')
                                <div
                                    class="w-8 h-8 rounded-full bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400 flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            @else
                                <div
                                    class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400 flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            @endif
                        </div>

                        <div class="flex-1 min-w-0">
                            <p
                                class="text-sm text-gray-900 dark:text-white {{ $notification->is_read ? 'font-medium' : 'font-bold' }}">
                                {{ $notification->title }}
                            </p>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5 line-clamp-3">
                                {{ $notification->message }}
                            </p>
                            <p class="text-[10px] font-medium text-gray-400 mt-1">
                                {{ $notification->created_at->diffForHumans() }}
                            </p>
                        </div>

                        @if(!$notification->is_read)
                            <button wire:click="markAsRead({{ $notification->id }})"
                                class="shrink-0 p-1 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 rounded transition-colors"
                                title="Отметить как прочитанное">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>
            @empty
                <div class="px-4 py-8 text-center">
                    <div
                        class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-50 dark:bg-gray-700/50 mb-3">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4">
                            </path>
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Всё чисто</p>
                    <p class="text-xs text-gray-500 mt-1">У вас нет новых уведомлений</p>
                </div>
            @endforelse
        </div>
    </div>
</div>