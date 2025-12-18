@props([
    'active' => false,
    'icon' => null,
    'label' => 'Menu',
])

<div x-data="{
    open: false,
    position: { top: 0, left: 0 },
    updatePosition() {
        if (this.open && this.$refs.button) {
            const rect = this.$refs.button.getBoundingClientRect();
            this.position = {
                top: rect.bottom + 4,
                left: rect.left
            };
        }
    }
}" 
    @resize.window="updatePosition()"
    @scroll.window="updatePosition()"
    x-init="$watch('open', value => { if (value) { setTimeout(() => updatePosition(), 10); } })"
    class="relative">
    <button 
        x-ref="button"
        type="button" 
        @click="open = !open; updatePosition();"
        :class="{
            'bg-[#EC8B18] text-white shadow-md': open || {{ $active ? 'true' : 'false' }},
            'text-gray-700 hover:bg-gray-100': !open && !{{ $active ? 'true' : 'false' }}
        }"
        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-md transition-colors duration-200 whitespace-nowrap">
        @if ($icon)
            <span class="shrink-0 w-5 h-5 flex items-center justify-center">{!! $icon !!}</span>
        @endif
        <span>{{ $label }}</span>
        <svg xmlns="https://www.w3.org/2000/svg" class="w-4 h-4 transition-transform duration-200"
            :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <div 
        x-show="open" 
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click.away="open = false"
        x-cloak
        class="fixed w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-9999"
        :style="`top: ${position.top}px; left: ${position.left}px;`">
        <div class="space-y-1">
            {{ $slot }}
        </div>
    </div>
</div>

