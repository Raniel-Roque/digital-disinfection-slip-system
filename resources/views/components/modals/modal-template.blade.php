@props([
    'show' => 'showModal',
    'title' => '',
    'maxWidth' => 'max-w-md',
    'backdropOpacity' => '80',
    'headerClass' => '',
])

@php
    $backdropClass = match ($backdropOpacity) {
        '40' => 'bg-black/40',
        '50' => 'bg-black/50',
        '60' => 'bg-black/60',
        '70' => 'bg-black/70',
        '80' => 'bg-black/80',
        '90' => 'bg-black/90',
        default => 'bg-black/80',
    };
@endphp

<div x-data="{ show: @entangle($show) }" x-show="show" x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
    {{-- Backdrop --}}
    <div class="fixed inset-0 {{ $backdropClass }} transition-opacity" @click="$wire.closeModal ? $wire.closeModal() : (show = false)"></div>

    {{-- Modal Panel --}}
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="show" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative bg-white rounded-xl shadow-xl w-full {{ $maxWidth }} z-50" @click.stop>
            {{-- Header --}}
            <div class="flex items-center justify-between p-4 border-b border-gray-200 {{ $headerClass }}">
                <div class="flex items-center gap-3">
                    @if (isset($header))
                        {{ $header }}
                    @endif
                    @if (isset($titleSlot))
                        <h3 class="text-lg font-semibold text-gray-900">{{ $titleSlot }}</h3>
                    @else
                <h3 class="text-lg font-semibold text-gray-900">{{ $title }}</h3>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    @if (isset($headerActions))
                        {{ $headerActions }}
                    @else
                    <button @click="show = false" class="text-gray-400 hover:text-gray-600 transition hover:cursor-pointer cursor-pointer">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    @endif
                </div>
            </div>

            {{-- Body (slot) --}}
            <div class="p-6 -mt-4">
                {{ $slot }}
            </div>

            {{-- Footer (optional slot) --}}
            @if (isset($footer))
                <div class="flex justify-end gap-3 p-4 rounded-b-xl -mt-4">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
</div>
