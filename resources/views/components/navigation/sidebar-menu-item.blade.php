@props([
    'href' => '#',
    'active' => false,
    'icon' => null,
    'indent' => false,
])

<a href="{{ $href }}"
    {{ $attributes->merge([
        'class' =>
            'flex items-center gap-3 px-3 py-2 text-sm font-semibold rounded-md transition-colors duration-200 cursor-pointer ' .
            ($active ? 'bg-gray-100/70 shadow-md hover:bg-gray-100' : 'text-gray-800 hover:bg-gray-100') .
            ($indent ? ' ml-6' : ''),
    ]) }}>
    @if ($icon)
        <span class="shrink-0 w-5 h-5 flex items-center justify-center">{!! $icon !!}</span>
    @endif
    <span>{{ $slot }}</span>
</a>
