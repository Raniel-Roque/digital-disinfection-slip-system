@props([
    'show' => 'showCancelConfirmation',
    'title' => 'DISCARD CHANGES?',
    'message' => 'Are you sure you want to cancel?',
    'warning' => 'All unsaved changes will be lost.',
    'onConfirm' => 'cancelEdit',
    'confirmText' => 'Discard',
    'cancelText' => 'Back',
])

<x-modals.modal-template :show="$show" :title="$title" max-width="max-w-md">
    <div class="text-center py-4">
        <svg class="mx-auto mb-4 text-yellow-500 w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
            </path>
        </svg>
        <p class="text-gray-700 text-lg mb-2">{{ $message }}</p>
        <p class="text-gray-500 text-sm">{{ $warning }}</p>
    </div>

    <x-slot name="footer">
        {{-- Mobile Layout --}}
        <div class="flex flex-col gap-2 w-full -mt-4 md:hidden">
            <x-buttons.submit-button wire:click="{{ $onConfirm }}" color="red" wire:loading.attr="disabled" wire:target="{{ $onConfirm }}">
                <span wire:loading.remove wire:target="{{ $onConfirm }}">{{ $confirmText }}</span>
                <span wire:loading wire:target="{{ $onConfirm }}" class="inline-flex items-center gap-2"><svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Discarding...
                </span>
            </x-buttons.submit-button>

            <x-buttons.submit-button wire:click="$set('{{ $show }}', false)" color="white" wire:loading.attr="disabled" wire:target="{{ $onConfirm }}">
                {{ $cancelText }}
            </x-buttons.submit-button>
        </div>

        {{-- Desktop Layout --}}
        <div class="hidden md:flex justify-end gap-3">
            <x-buttons.submit-button wire:click="$set('{{ $show }}', false)" color="white" wire:loading.attr="disabled" wire:target="{{ $onConfirm }}">
                {{ $cancelText }}
            </x-buttons.submit-button>
            <x-buttons.submit-button wire:click="{{ $onConfirm }}" color="red" wire:loading.attr="disabled" wire:target="{{ $onConfirm }}">
                <span wire:loading.remove wire:target="{{ $onConfirm }}">{{ $confirmText }}</span>
                <span wire:loading wire:target="{{ $onConfirm }}" class="inline-flex items-center gap-2"><svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Discarding...
                </span>
            </x-buttons.submit-button>
        </div>
    </x-slot>
</x-modals.modal-template>
