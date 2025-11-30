@props([
    'active' => false, 
    'icon' => null,
    'label' => 'Menu',
])

<div x-data="{ open: false }" class="w-full">
    <button
        type="button"
        @click="open = !open"
        :class="{
            'bg-gray-100/70 shadow-md': !open && {{ $active ? 'true' : 'false' }},
            'text-gray-800 hover:bg-gray-100': open || !{{ $active ? 'true' : 'false' }}
        }"
        class="w-full flex items-center justify-between px-3 py-2 text-sm font-semibold rounded-md transition-colors duration-200"
    >
        <div class="flex items-center gap-3">
            @if($icon)
                <span class="shrink-0 w-5 h-5 flex items-center justify-center">{!! $icon !!}</span>
            @endif
            <span>{{ $label }}</span>
        </div>

        <svg 
            xmlns="http://www.w3.org/2000/svg" 
            class="w-4 h-4 transition-transform duration-200"
            :class="open ? 'rotate-180' : ''"
            fill="none" 
            viewBox="0 0 24 24" 
            stroke="currentColor"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <div x-show="open" x-transition class="space-y-1 mt-1">
        {{ $slot }}
    </div>
</div>
