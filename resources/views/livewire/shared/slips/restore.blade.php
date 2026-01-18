<div>
    <x-modals.modal-template show="showModal" max-width="max-w-lg">
        <x-slot name="header">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-12 h-12 bg-green-100 rounded-full">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                        </path>
                    </svg>
                </div>
                <h3 class="ml-4 text-lg font-semibold text-gray-900">Restore Slip</h3>
            </div>
        </x-slot>

        @csrf
        <p class="text-sm text-gray-600">
            Are you sure you want to restore slip <span class="font-semibold text-gray-900">{{ $slipSlipId }}</span>? This will make the slip available again in the system.
        </p>

        <x-slot name="footer">
            <button wire:click="closeModal" wire:loading.attr="disabled" wire:target="restore"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 disabled:opacity-50 disabled:cursor-not-allowed">
                Cancel
            </button>
            <button wire:click="restore" wire:loading.attr="disabled" wire:target="restore"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed">
                <span wire:loading.remove wire:target="restore">Restore Slip</span>
                <span wire:loading.inline-flex wire:target="restore" class="inline-flex items-center gap-2"><svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Restoring...</span>
            </button>
        </x-slot>
    </x-modals.modal-template>
</div>
