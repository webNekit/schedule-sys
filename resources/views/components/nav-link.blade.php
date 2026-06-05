@props(['href', 'active' => false, 'emoji' => ''])

@php
$classes = ($active ?? false)
    ? 'w-full flex items-center px-3 py-2 rounded-lg text-sm font-medium bg-zinc-900 text-white border-l-2 border-emerald-500 transition-colors shrink-0'
    : 'w-full flex items-center px-3 py-2 rounded-lg text-sm font-medium text-zinc-400 hover:text-zinc-100 hover:bg-zinc-900/60 border-l-2 border-transparent transition-colors shrink-0';
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    <span class="truncate">{{ $slot }}</span>
</a>
