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
        <x-buttons.submit-button wire:click="$set('{{ $show }}', false)" color="white" wire:loading.attr="disabled" wire:target="{{ $onConfirm }}">
            {{ $cancelText }}
        </x-buttons.submit-button>
        <x-buttons.submit-button wire:click="{{ $onConfirm }}" color="red" wire:loading.attr="disabled" wire:target="{{ $onConfirm }}">
            <span wire:loading.remove wire:target="{{ $onConfirm }}">{{ $confirmText }}</span>
            <span wire:loading wire:target="{{ $onConfirm }}" class="inline-flex items-center gap-2">
                Discarding...
            </span>
        </x-buttons.submit-button>
    </x-slot>
</x-modals.modal-template>
