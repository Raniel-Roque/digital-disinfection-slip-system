<div>
    <x-modals.modal-template show="showModal" title="Create Guard" max-width="max-w-lg">
        @csrf
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">First Name <span
                        class="text-red-500">*</span></label>
                <input type="text" wire:model="first_name" maxlength="70"
                    class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Enter first name">
                @error('first_name')
                    <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name <span
                        class="text-gray-400">(Optional)</span></label>
                <input type="text" wire:model="middle_name" maxlength="70"
                    class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Enter middle name">
                @error('middle_name')
                    <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Last Name <span
                        class="text-red-500">*</span></label>
                <input type="text" wire:model="last_name" maxlength="70"
                    class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Enter last name">
                @error('last_name')
                    <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                @enderror
            </div>

            {{-- Super Guard Toggle - Only if showSuperGuardEdit is enabled --}}
            @if ($showSuperGuardEdit)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Super Guard</label>
                <label class="relative inline-flex items-center cursor-pointer" x-data="{ superGuard: @entangle('super_guard').live }">
                    <input type="checkbox" wire:model.live="super_guard" x-model="superGuard" class="sr-only">
                    <div class="w-11 h-6 rounded-full focus-within:outline-none focus-within:ring-4 focus-within:ring-blue-300 transition-colors duration-200 relative" :class="superGuard ? 'bg-blue-600' : 'bg-gray-200'">
                        <div class="absolute top-[2px] left-[2px] bg-white border border-gray-300 rounded-full h-5 w-5 transition-transform duration-200" :class="superGuard ? 'translate-x-5' : 'translate-x-0'"></div>
                    </div>
                    <span class="ml-3 text-sm text-gray-700" x-text="superGuard ? 'Enabled - This guard is a super guard' : 'Disabled - This guard is a regular guard'"></span>
                </label>
            </div>
            @endif
        </div>

        <x-slot name="footer">
            <button wire:click="closeModal"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                Cancel
            </button>
            <button wire:click.prevent="create" wire:loading.attr="disabled" wire:target="create"
                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                <span wire:loading.remove wire:target="create">Create</span>
                <span wire:loading.inline-flex wire:target="create" class="inline-flex items-center gap-2"><svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Creating...</span>
            </button>
        </x-slot>
    </x-modals.modal-template>
</div>
