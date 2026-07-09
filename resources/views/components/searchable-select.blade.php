@props([
    'label'       => '',
    'model'       => '',
    'options'     => [],
    'optionValue' => 'id',
    'optionLabel' => 'label',
    'noneLabel'   => 'Не выбрано',
    'noneValue'   => '',
    'placeholder' => 'Поиск...',
    'dark'        => false,
    'required'    => false,
    'hint'        => '',
    'error'       => '',
])

@php
    $trigger    = $dark
        ? 'border-zinc-700 bg-zinc-900 text-zinc-100 hover:border-zinc-500'
        : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 hover:border-gray-400';
    $dropdown   = $dark ? 'bg-zinc-800 border-zinc-700' : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700';
    $searchCls  = $dark
        ? 'bg-zinc-900 border-zinc-600 text-zinc-100 placeholder-zinc-500 focus:border-zinc-400'
        : 'bg-gray-50 dark:bg-gray-700 border-gray-200 dark:border-gray-600 text-gray-900 dark:text-gray-100 focus:border-emerald-400';
    $itemBase   = $dark ? 'text-zinc-200 hover:bg-zinc-700' : 'text-gray-900 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-700';
    $itemActive = $dark ? 'bg-zinc-700 text-white'         : 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300';
    $noneItem   = $dark ? 'text-zinc-400 hover:bg-zinc-700' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700';
    $emptyText  = $dark ? 'text-zinc-500' : 'text-gray-400 dark:text-gray-500';
    $sepColor   = $dark ? 'border-zinc-700' : 'border-gray-100 dark:border-gray-700';
    $hintColor  = $dark ? 'text-zinc-500' : 'text-gray-400 dark:text-gray-500';
    $noneVal    = (string) $noneValue;
@endphp

{{--
    Варианты рендерятся как настоящие <option> на сервере, Alpine читает их из DOM
    в init() — это надёжно работает с кириллицей и не ломает HTML-атрибуты.
    Скрытый <select> с wire:model отвечает за связь с Livewire.
--}}
<div
    x-data="{
        search: '',
        open: false,
        value: '',
        options: [],
        noneLabel: @js($noneLabel),
        init() {
            const sel = this.$refs.native;
            this.options = Array.from(sel.options)
                .filter(o => o.value !== @js($noneVal))
                .map(o => ({ id: o.value, label: o.textContent.trim() }));
            this.value = sel.value;
            sel.addEventListener('change', () => { this.value = sel.value; });
        },
        get filtered() {
            if (!this.search) return this.options;
            const s = this.search.toLowerCase();
            return this.options.filter(o => o.label.toLowerCase().includes(s) || String(o.id).toLowerCase().includes(s));
        },
        get selectedLabel() {
            const v = String(this.value ?? '');
            const found = this.options.find(o => String(o.id) === v);
            return found ? found.label : this.noneLabel;
        },
        toggle() {
            this.open = !this.open;
            if (this.open) this.$nextTick(() => { if (this.$refs.si) this.$refs.si.focus(); });
        },
        choose(val) {
            const sel = this.$refs.native;
            sel.value = val;
            sel.dispatchEvent(new Event('input'));
            sel.dispatchEvent(new Event('change'));
            this.value = String(val);
            this.open = false;
            this.search = '';
        }
    }"
    @click.outside="open = false; search = ''"
    class="relative"
>
    @if ($label)
        <label class="block text-sm font-medium mb-1 {{ $dark ? 'text-zinc-300' : 'text-gray-700 dark:text-gray-300' }}">
            {{ $label }}@if ($required)<span class="text-red-500 ml-0.5">*</span>@endif
        </label>
    @endif

    {{-- Скрытый нативный select — источник вариантов и связь с Livewire --}}
    <select x-ref="native" wire:model.live="{{ $model }}" class="hidden" tabindex="-1" aria-hidden="true">
        <option value="{{ $noneValue }}">{{ $noneLabel }}</option>
        @foreach ($options as $opt)
            <option value="{{ $opt[$optionValue] }}">{{ $opt[$optionLabel] }}</option>
        @endforeach
    </select>

    {{-- Кнопка-триггер --}}
    <button
        type="button"
        @click="toggle()"
        class="w-full flex items-center justify-between gap-2 px-3 py-2 text-sm rounded-lg border outline-none transition-colors {{ $trigger }}"
    >
        <span x-text="selectedLabel" class="truncate text-left flex-1"></span>
        <svg class="w-4 h-4 opacity-50 shrink-0 transition-transform duration-150" :class="{ 'rotate-180': open }"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    {{-- Выпадающий список --}}
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="absolute z-50 mt-1 w-full rounded-lg border shadow-xl overflow-hidden {{ $dropdown }}"
    >
        <div class="p-2 border-b {{ $sepColor }}">
            <input type="text" x-model="search" x-ref="si" placeholder="{{ $placeholder }}"
                class="w-full px-2 py-1.5 text-sm rounded border outline-none transition-colors {{ $searchCls }}">
        </div>

        <ul class="max-h-56 overflow-y-auto">
            <li @mousedown.prevent="choose(@js($noneVal))"
                class="px-3 py-2 text-sm cursor-pointer transition-colors {{ $noneItem }}">{{ $noneLabel }}</li>

            <template x-for="item in filtered" :key="item.id">
                <li @mousedown.prevent="choose(item.id)"
                    class="px-3 py-2 text-sm cursor-pointer transition-colors"
                    :class="String(item.id) === String(value ?? '') ? '{{ $itemActive }}' : '{{ $itemBase }}'"
                    x-text="item.label"></li>
            </template>

            <li x-show="filtered.length === 0" class="px-3 py-2 text-sm {{ $emptyText }}">Ничего не найдено</li>
        </ul>
    </div>

    @if ($hint)
        <p class="text-xs mt-1 {{ $hintColor }}">{{ $hint }}</p>
    @endif
    @if ($error)
        <p class="text-xs text-red-500 mt-1">{{ $error }}</p>
    @endif
</div>
