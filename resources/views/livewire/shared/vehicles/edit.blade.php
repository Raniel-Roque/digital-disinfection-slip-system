<div>
    <x-modals.modal-template show="showModal" title="Edit Vehicle" max-width="max-w-md">
        @csrf
        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle <span
                                        class="text-red-500">*</span></label>
                                <input type="text" wire:model="vehicle" maxlength="20"
                                    class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent uppercase"
                                    placeholder="Enter vehicle">
                                @error('vehicle')
                                    <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                @enderror
                    </div>
                </div>

        <x-slot name="footer">
            <button wire:click="closeModal"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 hover:cursor-pointer cursor-pointer">
                Cancel
            </button>
            <button wire:click.prevent="update" wire:loading.attr="disabled" wire:target="update"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 hover:cursor-pointer transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                x-bind:disabled="!$wire.hasChanges">
                <span wire:loading.remove wire:target="update">Save Changes</span>
                <span wire:loading.inline-flex wire:target="update" class="inline-flex items-center gap-2"><svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Saving...</span>
            </button>
        </x-slot>
    </x-modals.modal-template>
</div>
