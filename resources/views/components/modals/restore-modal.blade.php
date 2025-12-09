@props([
    'show' => 'showRestoreModal',
    'title' => 'Restore Item',
    'name' => '',
    'onConfirm' => 'restoreItem',
    'confirmText' => 'Restore',
    'cancelText' => 'Cancel',
])

@if ($show)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        {{-- Backdrop --}}
        <div class="fixed inset-0 transition-opacity bg-black/80" wire:click="$set('showRestoreModal', false)"></div>

        {{-- Modal Panel --}}
        <div class="flex min-h-full items-center justify-center p-4">
            <div
                class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all w-full max-w-lg">
                <div class="px-6 py-4 bg-white border-b border-gray-200">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-12 h-12 bg-yellow-100 rounded-full">
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                </path>
                            </svg>
                        </div>
                        <h3 class="ml-4 text-lg font-semibold text-gray-900">{{ $title }}</h3>
                    </div>
                </div>

                <div class="px-6 py-4">
                    <p class="text-sm text-gray-600">
                        Are you sure you want to restore <span
                            class="font-semibold text-gray-900">{{ $name }}</span>? This will make the item
                        active again in the system.
                    </p>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                    <button wire:click="$set('showRestoreModal', false)" wire:loading.attr="disabled" wire:target="{{ $onConfirm }}"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 disabled:opacity-50 disabled:cursor-not-allowed">
                        {{ $cancelText }}
                    </button>
                    <button wire:click="{{ $onConfirm }}" wire:loading.attr="disabled" wire:target="{{ $onConfirm }}"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-yellow-600 rounded-lg hover:bg-yellow-700 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="{{ $onConfirm }}">{{ $confirmText }}</span>
                        <span wire:loading wire:target="{{ $onConfirm }}" class="inline-flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Restoring...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
