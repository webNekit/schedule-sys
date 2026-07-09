@php
    $base = $prop . '.' . $i;
    $f = $row['field'] ?? 'group_id';
    $numericOps = ['=' => '=', '!=' => '≠', '<' => '<', '<=' => '≤', '>' => '>', '>=' => '≥'];
    $eqOps = ['=' => 'это', '!=' => 'не это'];
@endphp
<div class="flex flex-wrap items-center gap-2 p-2 rounded-lg bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700">
    {{-- Поле --}}
    <select wire:model.live="{{ $base }}.field"
        class="rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-2 py-1.5 text-sm">
        @foreach ($fields as $fName)
            <option value="{{ $fName }}">{{ $fieldLabels[$fName] ?? $fName }}</option>
        @endforeach
    </select>

    @if ($f === 'weekday')
        <select wire:model="{{ $base }}.op" class="rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-2 py-1.5 text-sm">
            <option value="in">входит в</option>
            <option value="not_in">не входит в</option>
        </select>
        <div class="flex items-center gap-1.5">
            @foreach ([1 => 'Пн', 2 => 'Вт', 3 => 'Ср', 4 => 'Чт', 5 => 'Пт', 6 => 'Сб', 7 => 'Вс'] as $d => $lbl)
                <label class="inline-flex items-center gap-1 text-xs">
                    <input type="checkbox" value="{{ $d }}" wire:model="{{ $base }}.value"
                        class="rounded border-gray-300 text-emerald-600">
                    {{ $lbl }}
                </label>
            @endforeach
        </div>
    @else
        {{-- Оператор --}}
        <select wire:model="{{ $base }}.op" class="rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-2 py-1.5 text-sm">
            @if (in_array($f, ['lesson_number', 'course', 'shift', 'date'], true))
                @foreach ($numericOps as $opVal => $opLbl)
                    <option value="{{ $opVal }}">{{ $opLbl }}</option>
                @endforeach
            @else
                @foreach ($eqOps as $opVal => $opLbl)
                    <option value="{{ $opVal }}">{{ $opLbl }}</option>
                @endforeach
            @endif
        </select>

        {{-- Значение (зависит от поля) --}}
        @switch($f)
            @case('group_id')
                <select wire:model="{{ $base }}.value" class="rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-2 py-1.5 text-sm min-w-[160px]">
                    <option value="">— выберите —</option>
                    @foreach ($groups as $g)
                        <option value="{{ $g->id }}">{{ $g->name }}</option>
                    @endforeach
                </select>
                @break

            @case('teacher_id')
                <select wire:model="{{ $base }}.value" class="rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-2 py-1.5 text-sm min-w-[160px]">
                    <option value="">— выберите —</option>
                    @foreach ($teachers as $t)
                        <option value="{{ $t['id'] }}">{{ $t['label'] }}</option>
                    @endforeach
                </select>
                @break

            @case('discipline_id')
                <select wire:model="{{ $base }}.value" class="rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-2 py-1.5 text-sm min-w-[200px]">
                    <option value="">— выберите —</option>
                    @foreach ($disciplines as $d)
                        <option value="{{ $d['id'] }}">{{ $d['label'] }}</option>
                    @endforeach
                </select>
                @break

            @case('building_id')
                <select wire:model="{{ $base }}.value" class="rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-2 py-1.5 text-sm min-w-[140px]">
                    <option value="">— выберите —</option>
                    @foreach ($buildings as $b)
                        <option value="{{ $b['id'] }}">{{ $b['label'] }}</option>
                    @endforeach
                </select>
                @break

            @case('room_type_id')
                <select wire:model="{{ $base }}.value" class="rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-2 py-1.5 text-sm min-w-[140px]">
                    <option value="">— выберите —</option>
                    @foreach ($roomTypes as $rt)
                        <option value="{{ $rt['id'] }}">{{ $rt['label'] }}</option>
                    @endforeach
                </select>
                @break

            @case('room_id')
                <select wire:model="{{ $base }}.value" class="rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-2 py-1.5 text-sm min-w-[140px]">
                    <option value="">— выберите —</option>
                    @foreach ($rooms as $r)
                        <option value="{{ $r['id'] }}">{{ $r['label'] }}</option>
                    @endforeach
                </select>
                @break

            @case('lesson_type')
                <select wire:model="{{ $base }}.value" class="rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-2 py-1.5 text-sm min-w-[140px]">
                    <option value="">— выберите —</option>
                    @foreach ($lessonTypes as $lt)
                        <option value="{{ $lt['code'] }}">{{ $lt['label'] }}</option>
                    @endforeach
                </select>
                @break

            @case('date')
                <input type="date" wire:model="{{ $base }}.value"
                    class="rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-2 py-1.5 text-sm">
                @break

            @default
                <input type="number" wire:model="{{ $base }}.value" placeholder="число"
                    class="w-24 rounded-md border border-gray-300 dark:border-gray-600 dark:bg-gray-900 px-2 py-1.5 text-sm">
        @endswitch
    @endif

    <button type="button" wire:click="{{ $removeMethod }}({{ $i }})"
        class="ml-auto text-gray-400 hover:text-red-600 text-lg leading-none px-1" title="Удалить условие">&times;</button>
</div>
