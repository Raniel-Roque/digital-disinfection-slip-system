@props([
    'label' => 'Menu',
    'icon' => null,
    'active' => false,
])

<div x-data="{ open: false }" class="relative">
    <button 
        type="button" 
        @click="open = !open"
        :class="{
            'text-[#EC8B18]': open || {{ $active ? 'true' : 'false' }},
            'text-gray-600': !open && !{{ $active ? 'true' : 'false' }}
        }"
        class="flex flex-col items-center justify-center gap-1 py-2 h-full w-full transition-colors duration-200">
        @if ($icon)
            <span class="flex items-center justify-center">{!! $icon !!}</span>
        @endif
        <span class="text-xs font-medium">{{ $label }}</span>
    </button>

    <!-- Backdrop -->
    <div 
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="open = false"
        x-cloak
        class="fixed inset-0 bg-black/50 z-30 sm:hidden"
        style="display: none;">
    </div>

    <!-- Dropup Menu -->
    <div 
        x-show="open" 
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-4"
        @click.away="open = false"
        x-cloak
        class="fixed bottom-16 left-0 right-0 bg-white border-t border-gray-200 shadow-2xl z-40 sm:hidden max-h-[60vh] overflow-y-auto"
        style="display: none;">
        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
            <h3 class="text-sm font-semibold text-gray-900">{{ $label }}</h3>
        </div>
        <div class="py-2">
            {{ $slot }}
        </div>
    </div>
</div>

