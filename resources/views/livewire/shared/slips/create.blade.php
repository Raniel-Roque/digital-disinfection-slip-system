<div>
    @if ($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            {{-- Backdrop --}}
            <div class="fixed inset-0 transition-opacity bg-black/80" wire:click="closeModal"></div>

            {{-- Modal Panel --}}
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative bg-white rounded-xl shadow-xl w-full max-w-3xl overflow-visible" @click.stop>
                    {{-- Header --}}
                    <div class="flex items-center justify-between p-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">CREATE NEW DISINFECTION SLIP</h3>
                        <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    {{-- Body Fields --}}
                    <div class="space-y-0 -mx-6">
                        {{-- Vehicle --}}
                        <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-white">
                            <div class="font-semibold text-gray-500">Vehicle:<span class="text-red-500">*</span></div>
                            <div class="text-gray-900">
                                <x-forms.searchable-dropdown-paginated wire-model="vehicle_id" data-method="getPaginatedVehicles" search-property="searchVehicle"
                                    placeholder="Select vehicle..." search-placeholder="Search vehicles..." :per-page="20" />
                                @error('vehicle_id')
                                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        {{-- Origin --}}
                        <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-gray-100">
                            <div class="font-semibold text-gray-500">Origin:<span class="text-red-500">*</span></div>
                            <div class="text-gray-900">
                                <x-forms.searchable-dropdown-paginated wire-model="location_id" data-method="getPaginatedLocations" search-property="searchOrigin"
                                    placeholder="Select origin..." search-placeholder="Search locations..." :per-page="20" />
                                @error('location_id')
                                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        {{-- Destination --}}
                        <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-white">
                            <div class="font-semibold text-gray-500">Destination:<span class="text-red-500">*</span></div>
                            <div class="text-gray-900">
                                <x-forms.searchable-dropdown-paginated wire-model="destination_id" data-method="getPaginatedLocations" search-property="searchDestination"
                                    placeholder="Select destination..." search-placeholder="Search locations..." :per-page="20" />
                                @error('destination_id')
                                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        {{-- Driver Name --}}
                        <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-gray-100">
                            <div class="font-semibold text-gray-500">Driver Name:<span class="text-red-500">*</span></div>
                            <div class="text-gray-900">
                                <x-forms.searchable-dropdown-paginated wire-model="driver_id" data-method="getPaginatedDrivers" search-property="searchDriver"
                                    placeholder="Select driver..." search-placeholder="Search drivers..." :per-page="20" />
                                @error('driver_id')
                                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        {{-- Hatchery Guard --}}
                        <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-white">
                            <div class="font-semibold text-gray-500">Hatchery Guard:<span class="text-red-500">*</span></div>
                            <div class="text-gray-900">
                                <x-forms.searchable-dropdown-paginated wire-model="hatchery_guard_id" data-method="getPaginatedGuards"
                                    search-property="searchHatcheryGuard" placeholder="Select hatchery guard..."
                                    search-placeholder="Search guards..." :per-page="20" />
                                @error('hatchery_guard_id')
                                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        {{-- Receiving Guard (Optional) --}}
                        <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-gray-100">
                            <div class="font-semibold text-gray-500">Receiving Guard:</div>
                            <div class="text-gray-900">
                                <x-forms.searchable-dropdown-paginated wire-model="received_guard_id" data-method="getPaginatedGuards"
                                    search-property="searchReceivedGuard" placeholder="Select receiving guard..."
                                    search-placeholder="Search guards..." :per-page="20" />
                                @error('received_guard_id')
                                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        {{-- Reason --}}
                        <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-white">
                            <div class="font-semibold text-gray-500">Reason:<span class="text-red-500">*</span></div>
                            <div class="text-gray-900">
                                <x-forms.searchable-dropdown-paginated wire-model="reason_id" data-method="getPaginatedReasons" search-property="searchReason"
                                    placeholder="Select reason..." search-placeholder="Search reasons..." :per-page="20" />
                                @error('reason_id')
                                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        {{-- Remarks for Disinfection --}}
                        <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-gray-100">
                            <div class="font-semibold text-gray-500">Remarks:</div>
                            <div class="text-gray-900">
                                <textarea wire:model="remarks_for_disinfection"
                                    class="w-full border rounded px-2 py-1 text-sm border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                    rows="6" placeholder="Enter remarks..."></textarea>
                                @error('remarks_for_disinfection')
                                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="flex justify-end gap-2 p-4 border-t border-gray-200 -mt-6">
                        <x-buttons.submit-button wire:click="closeModal" color="white" wire:loading.attr="disabled" wire:target="createSlip">
                            Cancel
                        </x-buttons.submit-button>

                        <x-buttons.submit-button wire:click.prevent="createSlip" color="blue" wire:loading.attr="disabled" wire:target="createSlip">
                            <span wire:loading.remove wire:target="createSlip">Create</span>
                            <span wire:loading.inline-flex wire:target="createSlip" class="inline-flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Creating...
                            </span>
                        </x-buttons.submit-button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Cancel Confirmation Modal --}}
    <x-modals.unsaved-confirmation show="showCancelConfirmation" title="DISCARD CHANGES?"
        message="Are you sure you want to cancel?" warning="All unsaved changes will be lost." onConfirm="cancelCreate"
        confirmText="Discard" cancelText="Back" />
</div>
