@props([
    'label' => '',
    'placeholder' => 'Поиск...',
    'model' => '',
    'error' => '',
    'options' => [],
    'optionValue' => 'id',
    'optionLabel' => 'name',
    'noneLabel' => 'Не выбрано',
])

<div>
    @if ($label)
        <label class="block text-sm font-medium mb-1">{{ $label }}</label>
    @endif

    <div x-data="{
        search: '',
        open: false,
        get filtered() {
            if (!this.search) return {{ json_encode($options) }};
            const s = this.search.toLowerCase();
            return {{ json_encode($options) }}.filter(o => {
                const label = o['{{ $optionLabel }}']?.toString().toLowerCase() || '';
                return label.includes(s);
            });
        },
        select(id) {
            this.$wire.set('{{ $model }}', id);
            this.open = false;
            this.search = '';
        }
    }" class="relative">
        <input type="text" x-model="search" @focus="open = true" @blur="setTimeout(() => open = false, 200)"
            placeholder="{{ $placeholder }}"
            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm">

        <select wire:model="{{ $model }}" class="hidden" x-ref="nativeSelect">
            <option value="">{{ $noneLabel }}</option>
            @foreach ($options as $option)
                <option value="{{ $option[$optionValue] }}">{{ $option[$optionLabel] }}</option>
            @endforeach
        </select>

        <ul x-show="open && search.length > 0" x-cloak
            class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-48 overflow-y-auto">
            <template x-for="item in filtered" :key="item['{{ $optionValue }}']">
                <li @mousedown.prevent="select(item['{{ $optionValue }}'])"
                    class="px-3 py-2 text-sm cursor-pointer hover:bg-emerald-50 dark:hover:bg-emerald-900/30 transition-colors"
                    x-text="item['{{ $optionLabel }}']">
                </li>
            </template>
            <li x-show="filtered.length === 0" class="px-3 py-2 text-sm text-gray-400">Ничего не найдено</li>
        </ul>
    </div>

    @if ($error)
        <p class="text-xs text-red-600 mt-1">{{ $error }}</p>
    @endif
</div>
