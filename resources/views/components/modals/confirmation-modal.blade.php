@props([
    'show' => 'showConfirmation',
    'title' => 'Confirm Action',
    'message' => 'Are you sure?',
    'name' => '', // For delete-modal pattern: "Are you sure you want to delete {name}?"
    'details' => '',
    'warning' => 'This action cannot be undone!',
    'onConfirm' => 'confirmAction',
    'confirmText' => 'Confirm',
    'cancelText' => 'Cancel',
    'confirmColor' => 'red', // red, orange, blue, etc.
    'icon' => 'warning', // warning, info, danger
    'useAlpine' => true, // Use Alpine.js @entangle or @if directive
    'showWarning' => true, // Show warning message
])

@php
    $backdropClass = 'bg-black/80';
    $iconSvg = match($icon) {
        'warning' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>',
        'danger' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>',
        default => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
    };
    $iconColor = match($icon) {
        'warning' => 'text-yellow-500',
        'danger' => 'text-red-600',
        default => 'text-blue-500',
    };
    $iconBg = match($icon) {
        'warning' => 'bg-yellow-100',
        'danger' => 'bg-red-100',
        default => 'bg-blue-100',
    };
@endphp

@if($useAlpine)
    <x-modals.modal-template :show="$show" :title="$title" max-width="max-w-md" z-index="60">
        <div class="text-center py-4">
            <svg class="mx-auto mb-4 {{ $iconColor }} w-16 h-16" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                {!! $iconSvg !!}
            </svg>
            @if ($name)
                <p class="text-sm text-gray-600 mb-2">
                    {{ $message }}
                    <span class="font-semibold text-gray-900">{{ $name }}</span>?
                </p>
            @else
                <p class="text-gray-700 text-lg mb-2">{{ $message }}</p>
            @endif
            @if ($details)
                <div class="text-gray-700 mb-2">{!! $details !!}</div>
            @endif
            @if ($showWarning && $warning)
                <p class="text-red-600 text-sm font-semibold">{{ $warning }}</p>
            @endif
        </div>

        <x-slot name="footer">
            {{-- Mobile Layout --}}
            <div class="flex flex-col gap-2 w-full -mt-4 md:hidden">
                <x-buttons.submit-button wire:click.prevent="{{ $onConfirm }}" :color="$confirmColor" wire:loading.attr="disabled" wire:target="{{ $onConfirm }}">
                    <span wire:loading.remove wire:target="{{ $onConfirm }}">{{ $confirmText }}</span>
                    <span wire:loading.inline-flex wire:target="{{ $onConfirm }}">
                        <span class="inline-flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Processing...
                        </span>
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
                <x-buttons.submit-button wire:click.prevent="{{ $onConfirm }}" :color="$confirmColor" wire:loading.attr="disabled" wire:target="{{ $onConfirm }}">
                    <span wire:loading.remove wire:target="{{ $onConfirm }}">{{ $confirmText }}</span>
                    <span wire:loading.inline-flex wire:target="{{ $onConfirm }}">
                        <span class="inline-flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Processing...
                        </span>
                    </span>
                </x-buttons.submit-button>
            </div>
        </x-slot>
    </x-modals.modal-template>
@else
    <x-modals.modal-template :show="$show" max-width="max-w-lg" z-index="60">
        <x-slot name="header">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-12 h-12 {{ $iconBg }} rounded-full">
                    <svg class="w-6 h-6 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        {!! $iconSvg !!}
                    </svg>
                </div>
                <h3 class="ml-4 text-lg font-semibold text-gray-900">{{ $title }}</h3>
            </div>
        </x-slot>

        <p class="text-sm text-gray-600">
            @if ($name)
                {{ $message }}
                <span class="font-semibold text-gray-900">{{ $name }}</span>?
                @if ($showWarning && $warning)
                    <span class="block mt-2">{{ $warning }}</span>
                @endif
            @else
                {{ $message }}
                @if ($details)
                    <span class="font-semibold text-gray-900">{!! $details !!}</span>
                @endif
                @if ($showWarning && $warning)
                    <span class="block mt-2 text-red-600 text-sm font-semibold">{{ $warning }}</span>
                @endif
            @endif
        </p>

        <x-slot name="footer">
            <button wire:click="$set('{{ $show }}', false)" wire:loading.attr="disabled" wire:target="{{ $onConfirm }}"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 disabled:opacity-50 disabled:cursor-not-allowed">
                {{ $cancelText }}
            </button>
            <button wire:click="{{ $onConfirm }}" wire:loading.attr="disabled" wire:target="{{ $onConfirm }}"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-{{ $confirmColor }}-600 rounded-lg hover:bg-{{ $confirmColor }}-700 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-{{ $confirmColor }}-500 disabled:opacity-50 disabled:cursor-not-allowed">
                <span wire:loading.remove wire:target="{{ $onConfirm }}">{{ $confirmText }}</span>
                <span wire:loading.inline-flex wire:target="{{ $onConfirm }}" class="inline-flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Processing...
                </span>
            </button>
        </x-slot>
    </x-modals.modal-template>
@endif
