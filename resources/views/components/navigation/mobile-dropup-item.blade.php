@props([
    'href' => '#',
    'active' => false,
    'icon' => null,
])

<a href="{{ $href }}"
    {{ $attributes->merge([
        'class' =>
            'flex items-center gap-3 px-4 py-3 text-sm font-medium transition-colors duration-200 ' .
            ($active ? 'bg-gray-100 text-[#EC8B18]' : 'text-gray-700 hover:bg-gray-50'),
    ]) }}>
    @if ($icon)
        <span class="shrink-0 w-5 h-5 flex items-center justify-center">{!! $icon !!}</span>
    @endif
    <span>{{ $slot }}</span>
    @if ($active)
        <svg xmlns="https://www.w3.org/2000/svg" class="w-4 h-4 ml-auto text-[#EC8B18]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
        </svg>
    @endif
</a>

