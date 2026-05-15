@props(['href', 'active' => false, 'emoji' => ''])

@php
$classes = ($active ?? false)
    ? 'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-emerald-400 bg-emerald-900/20 border-l-[3px] border-emerald-500 transition-all duration-150'
    : 'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-400 hover:text-white hover:bg-gray-800 border-l-[3px] border-transparent transition-all duration-150';
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    @if($emoji)
        <span class="w-5 h-5 flex items-center justify-center text-base shrink-0">{{ $emoji }}</span>
    @endif
    <span class="flex-1 min-w-0 truncate">{{ $slot }}</span>
</a>
