@props([
    'href' => '#',
    'active' => false,
    'icon' => null,
])

<a href="{{ $href }}"
    {{ $attributes->merge([
        'class' =>
            'flex flex-col items-center justify-center gap-1 py-2 h-full w-full transition-colors duration-200 ' .
            ($active ? 'text-[#EC8B18]' : 'text-gray-600'),
    ]) }}>
    @if ($icon)
        <span class="flex items-center justify-center">{!! $icon !!}</span>
    @endif
    <span class="text-xs font-medium">{{ $slot }}</span>
</a>

