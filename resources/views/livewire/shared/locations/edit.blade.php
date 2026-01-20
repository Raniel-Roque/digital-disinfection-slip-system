<div>
    <x-modals.modal-template show="showModal" title="Edit Location" max-width="max-w-lg">
        @csrf
        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Location Name <span
                                        class="text-red-500">*</span></label>
                                <input type="text" wire:model.live="location_name"
                                    class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="Enter location name">
                                @error('location_name')
                                    <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Logo Section --}}
                            <div>
                                <div class="mb-3">
                                    <label class="text-sm font-medium text-gray-700">Logo <span
                                            class="text-gray-400">(Optional)</span>
                                    
                                    {{-- Remove/Cancel/Clear buttons after Optional --}}
                                    @if ($logo)
                                        {{-- Clear button for newly selected file --}}
                                        <button wire:click="clearLogo" type="button"
                                            class="ml-2 text-xs text-red-600 hover:text-red-800 hover:cursor-pointer cursor-pointer">
                                            Clear
                                        </button>
                                    @elseif ($this->editLogoPath && $this->editLogoPath !== $defaultLogoPath)
                                        @if (!$remove_logo)
                                            <button wire:click="removeLogo" type="button"
                                                class="ml-2 text-xs text-red-600 hover:text-red-800 hover:cursor-pointer cursor-pointer">
                                                Remove Logo
                                            </button>
                                        @else
                                            <button wire:click="cancelRemoveLogo" type="button"
                                                class="ml-2 text-xs text-blue-600 hover:text-blue-800 hover:cursor-pointer cursor-pointer">
                                                Cancel Remove
                                            </button>
                                        @endif
                                    @endif
                                    </label>
                                </div>

                                <div class="space-y-3">
                                    {{-- Choose Image Button - Always available unless a file is already selected --}}
                                    @if (!$logo)
                                    <label
                                        class="cursor-pointer inline-flex items-center justify-center w-full px-4 py-2.5 bg-white border-2 border-dashed border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:border-blue-400 hover:bg-blue-50 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                        @if ($this->editLogoPath && !$remove_logo && $this->editLogoPath !== $defaultLogoPath)
                                            Replace Image
                                        @else
                                            Choose Image
                                        @endif
                                        <input type="file" wire:model="logo" class="hidden"
                                            accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                                    </label>
                                    @endif

                                    {{-- Logo Preview - Only show if there's an actual logo (not default) --}}
                                    @if ($logo)
                                        <div class="flex items-center justify-center bg-white rounded-lg p-4 border border-gray-200 h-48">
                                            <img src="{{ $logo->temporaryUrl() }}" alt="Logo preview"
                                                class="max-w-full max-h-full w-auto h-auto object-contain">
                                        </div>
                                        
                                        {{-- File Info for newly selected file - Below preview --}}
                                        <div class="bg-gray-50 rounded-lg px-3 py-2 border border-gray-200">
                                            <p class="text-sm text-gray-700 truncate"
                                                title="{{ $logo->getClientOriginalName() }}">
                                                {{ $logo->getClientOriginalName() }}
                                            </p>
                                        </div>
                                    @elseif ($this->editLogoPath && !$remove_logo && $this->editLogoPath !== $defaultLogoPath)
                                        <div class="flex items-center justify-center bg-white rounded-lg p-4 border border-gray-200 h-48">
                                            <img src="{{ asset('storage/' . $this->editLogoPath) }}"
                                                alt="Current logo" class="max-w-full max-h-full w-auto h-auto object-contain">
                                        </div>
                                        
                                        {{-- File Info - Under preview --}}
                                        <div class="bg-gray-50 rounded-lg px-3 py-2 border border-gray-200">
                                            <p class="text-sm text-gray-700 truncate"
                                                title="{{ $this->editLogoPath }}">
                                                Current: {{ basename($this->editLogoPath) }}
                                            </p>
                                        </div>
                                    @endif
                                </div>

                                @error('logo')
                                    <span class="text-red-500 text-xs mt-2 block">{{ $message }}</span>
                                @enderror
                                <p class="text-xs text-gray-600 mt-3 leading-relaxed">
                                    Supported formats: JPEG, PNG, GIF, WebP (Max 15MB)
                                </p>
                            </div>

                            {{-- Create Slip Toggle --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Allow Create Slip</label>
                                <label class="relative inline-flex items-center cursor-pointer" x-data="{ createSlip: @entangle('create_slip').live }">
                                    <input type="checkbox" wire:model.live="create_slip" x-model="createSlip" class="sr-only">
                                    <div class="w-11 h-6 rounded-full focus-within:outline-none focus-within:ring-4 focus-within:ring-blue-300 transition-colors duration-200 relative" :class="createSlip ? 'bg-blue-600' : 'bg-gray-200'">
                                        <div class="absolute top-[2px] left-[2px] bg-white border border-gray-300 rounded-full h-5 w-5 transition-transform duration-200" :class="createSlip ? 'translate-x-5' : 'translate-x-0'"></div>
                                    </div>
                                    <span class="ml-3 text-sm text-gray-700" x-text="createSlip ? 'Enabled - Guards can create slips at this location' : 'Disabled - Guards cannot create slips at this location'"></span>
                                </label>
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
