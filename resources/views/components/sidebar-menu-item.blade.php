@props([
    'href' => '#',
    'active' => false,
    'icon' => null,
])

<a 
    href="{{ $href }}"
    {{ $attributes->merge([
        'class' => 'flex items-center gap-3 px-3 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-100 rounded-md transition-colors duration-200 ' . ($active ? 'text-[#EC8B18]' : '')
    ]) }}
>
    @if($icon)
        <span class="shrink-0 w-5 h-5">
            {!! $icon !!}
        </span>
    @endif
    <span>{{ $slot }}</span>
</a>

