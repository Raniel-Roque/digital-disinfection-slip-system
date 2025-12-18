@props([
    'href' => '#',
    'active' => false,
    'icon' => null,
])

<a href="{{ $href }}"
    {{ $attributes->merge([
        'class' =>
            'inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-md transition-colors duration-200 cursor-pointer whitespace-nowrap ' .
            ($active ? 'bg-[#EC8B18] text-white shadow-md' : 'text-gray-700 hover:bg-gray-100'),
    ]) }}>
    @if ($icon)
        <span class="shrink-0 w-5 h-5 flex items-center justify-center">{!! $icon !!}</span>
    @endif
    <span>{{ $slot }}</span>
</a>

