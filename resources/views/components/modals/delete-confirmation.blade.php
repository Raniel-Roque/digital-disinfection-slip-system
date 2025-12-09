@props([
    'show' => 'showDeleteConfirmation',
    'title' => 'DELETE SLIP?',
    'message' => 'Delete this disinfection slip?',
    'details' => '',
    'warning' => 'This action cannot be undone!',
    'onConfirm' => 'deleteSlip',
    'confirmText' => 'Yes, Delete',
    'cancelText' => 'Cancel',
])

@php
    $backdropClass = 'bg-black/80';
@endphp

<div x-data="{ show: @entangle($show) }" x-show="show" x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0" class="fixed inset-0" style="display: none; z-index: 60;">
    {{-- Backdrop --}}
    <div class="fixed inset-0 {{ $backdropClass }} transition-opacity" @click="show = false"></div>

    {{-- Modal Panel --}}
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="show" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative bg-white rounded-xl shadow-xl w-full max-w-md overflow-visible" @click.stop>
            {{-- Header --}}
            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">{{ $title }}</h3>
                <button @click="show = false" class="text-gray-400 hover:text-gray-600 transition hover:cursor-pointer">
                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="p-6 overflow-visible">
                <div class="text-center py-4">
                    <svg class="mx-auto mb-4 text-yellow-500 w-16 h-16" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                        </path>
                    </svg>
                    <p class="text-gray-700 text-lg mb-2">{{ $message }}</p>
                    @if ($details)
                        <div class="text-gray-700 mb-2">{!! $details !!}</div>
                    @endif
                    <p class="text-red-600 text-sm font-semibold">{{ $warning }}</p>
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex justify-end gap-3 p-4 rounded-b-xl">
                <x-buttons.submit-button wire:click="$set('{{ $show }}', false)" color="white" wire:loading.attr="disabled" wire:target="{{ $onConfirm }}">
                    {{ $cancelText }}
                </x-buttons.submit-button>
                <x-buttons.submit-button wire:click="{{ $onConfirm }}" color="red" wire:loading.attr="disabled" wire:target="{{ $onConfirm }}">
                    <span wire:loading.remove wire:target="{{ $onConfirm }}">{{ $confirmText }}</span>
                    <span wire:loading wire:target="{{ $onConfirm }}" class="inline-flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Deleting...
                    </span>
                </x-buttons.submit-button>
            </div>
        </div>
    </div>
</div>
