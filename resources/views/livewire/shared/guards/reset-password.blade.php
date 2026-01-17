<div>
    @if ($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
            aria-modal="true">
            {{-- Backdrop --}}
            <div class="fixed inset-0 transition-opacity bg-black/80" wire:click="closeModal"></div>

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
                                        d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z">
                                    </path>
                                </svg>
                            </div>
                            <h3 class="ml-4 text-lg font-semibold text-gray-900">Reset Password</h3>
                        </div>
                    </div>

                    <div class="px-6 py-4">
                        @csrf
                        <p class="text-sm text-gray-600">
                            Are you sure you want to reset this guard's password? The password will be reset to the
                            default password "<span
                                class="font-medium text-gray-900">{{ $this->defaultPassword }}</span>".
                        </p>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                        <button wire:click="closeModal" wire:loading.attr="disabled" wire:target="resetPassword"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 disabled:opacity-50 disabled:cursor-not-allowed hover:cursor-pointer cursor-pointer">
                            Cancel
                        </button>
                        <button wire:click="resetPassword" wire:loading.attr="disabled" wire:target="resetPassword"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-yellow-600 rounded-lg hover:bg-yellow-700 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 disabled:opacity-50 disabled:cursor-not-allowed hover:cursor-pointer cursor-pointer">
                            <span wire:loading.remove wire:target="resetPassword">Reset Password</span>
                            <span wire:loading.inline-flex wire:target="resetPassword" class="inline-flex items-center gap-2"><svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Resetting...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
