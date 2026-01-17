<div>
    @if ($showModal && $selectedSlip)
        @php
            $status = $editStatus ?? $selectedSlip->status ?? null;
            $headerClass = '';
            if ($status == 0) {
                $headerClass = 'border-t-4 border-t-gray-500 bg-gray-50';
            } elseif ($status == 1) {
                $headerClass = 'border-t-4 border-t-blue-500 bg-blue-50';
            } elseif ($status == 2) {
                $headerClass = 'border-t-4 border-t-yellow-500 bg-yellow-50';
            }
        @endphp

        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="fixed inset-0 transition-opacity bg-black/80" wire:click="closeModal"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative bg-white rounded-xl shadow-xl w-full max-w-3xl overflow-visible {{ $headerClass }}" @click.stop>
                    <div class="flex items-center justify-between p-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">{{ strtoupper(($selectedSlip->location?->location_name ?? '') . ' DISINFECTION SLIP DETAILS') }}</h3>
                        <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="border-b border-gray-200 px-6 py-2 bg-gray-50 -mx-6 -mt-6 mb-2">
                        <div class="grid grid-cols-[1fr_1fr_auto] gap-4 items-start text-xs">
                            <div>
                                <div class="font-semibold text-gray-500 mb-0.5">Date:</div>
                                <div class="text-gray-900">{{ $selectedSlip->created_at->format('M d, Y') }}</div>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-500 mb-0.5">Slip No:</div>
                                <div class="text-gray-900 font-semibold">{{ $selectedSlip->slip_id }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-0 -mx-6">
                        <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-white">
                            <div class="font-semibold text-gray-500">Status:<span class="text-red-500">*</span></div>
                            <div class="text-gray-900">
                                <select wire:model="editStatus"
                                    class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-blue-500 hover:cursor-pointer cursor-pointer">
                                    <option value="0">Pending</option>
                                    <option value="1">Disinfecting</option>
                                    <option value="2">In-Transit</option>
                                </select>
                                @error('editStatus')
                                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-gray-100">
                            <div class="font-semibold text-gray-500">Vehicle:<span class="text-red-500">*</span></div>
                            <div class="text-gray-900">
                                @php
                                    $isVehicleSoftDeleted = $selectedSlip && $selectedSlip->vehicle && $selectedSlip->vehicle->trashed();
                                @endphp
                                <x-forms.searchable-dropdown-paginated wire-model="editVehicleId" data-method="getPaginatedVehicles" search-property="searchEditVehicle"
                                    placeholder="Select vehicle..." search-placeholder="Search vehicles..." :per-page="20"
                                    :disabled="$isVehicleSoftDeleted" />
                                @if ($isVehicleSoftDeleted)
                                    <p class="text-xs text-red-600 mt-1">This vehicle has been deleted and cannot be changed.</p>
                                @endif
                                @error('editVehicleId')
                                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-white">
                            <div class="font-semibold text-gray-500">Driver:<span class="text-red-500">*</span></div>
                            <div class="text-gray-900">
                                <x-forms.searchable-dropdown-paginated wire-model="editDriverId" data-method="getPaginatedDrivers"
                                    search-property="searchEditDriver" placeholder="Select driver..."
                                    search-placeholder="Search drivers..." :per-page="20" />
                                @error('editDriverId')
                                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        @if ($status == 0 || $status == 1 || $status == 2)
                            <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-gray-100">
                                <div class="font-semibold text-gray-500">Origin:<span class="text-red-500">*</span></div>
                                <div class="text-gray-900">
                                    <x-forms.searchable-dropdown-paginated wire-model="editLocationId" data-method="getPaginatedLocations"
                                        search-property="searchEditOrigin" placeholder="Select origin..."
                                        search-placeholder="Search locations..." :per-page="20" />
                                    @error('editLocationId')
                                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        @endif

                        <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-white">
                            <div class="font-semibold text-gray-500">Destination:<span class="text-red-500">*</span></div>
                            <div class="text-gray-900">
                                <x-forms.searchable-dropdown-paginated wire-model="editDestinationId" data-method="getPaginatedLocations"
                                    search-property="searchEditDestination" placeholder="Select destination..."
                                    search-placeholder="Search locations..." :per-page="20" />
                                @error('editDestinationId')
                                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-gray-100">
                            <div class="font-semibold text-gray-500">Remarks:</div>
                            <div class="text-gray-900">
                                <textarea wire:model="editRemarksForDisinfection" class="w-full border rounded px-2 py-2 text-sm" rows="6"></textarea>
                                @error('editRemarksForDisinfection')
                                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 px-6 py-2 bg-gray-50 -mx-6 -mb-6 mt-2">
                        <div class="grid grid-cols-2 gap-4 text-xs">
                            <div>
                                <div class="font-semibold text-gray-500 mb-0.5">Hatchery Guard:<span class="text-red-500">*</span></div>
                                <div class="text-gray-900">
                                    <x-forms.searchable-dropdown-paginated wire-model="editHatcheryGuardId" data-method="getPaginatedGuards"
                                        search-property="searchEditHatcheryGuard" placeholder="Select hatchery guard..."
                                        search-placeholder="Search guards..." :per-page="20" />
                                    @error('editHatcheryGuardId')
                                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div>
                                <div class="flex items-start justify-between mb-0.5">
                                    <div class="font-semibold text-gray-500">
                                        Received By:
                                        @if ($status == 1 || $status == 2)
                                            <span class="text-red-500">*</span>
                                        @endif
                                    </div>
                                    @if ($status == 0)
                                        <div x-data="{ editReceivedGuardId: @entangle('editReceivedGuardId') }">
                                            <button type="button" x-show="editReceivedGuardId"
                                                wire:click="$set('editReceivedGuardId', null)"
                                                class="text-xs text-blue-600 hover:text-blue-800 font-medium hover:cursor-pointer cursor-pointer" style="display: none;">
                                                Clear
                                            </button>
                                        </div>
                                    @endif
                                </div>
                                <div class="text-gray-900">
                                    <x-forms.searchable-dropdown-paginated wire-model="editReceivedGuardId" data-method="getPaginatedGuards"
                                        search-property="searchEditReceivedGuard" placeholder="Select receiving guard..."
                                        search-placeholder="Search guards..." :per-page="20" />
                                    @error('editReceivedGuardId')
                                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 p-4 border-t border-gray-200 -mt-6">
                        <x-buttons.submit-button wire:click="closeModal" color="white" wire:loading.attr="disabled" wire:target="saveEdit">
                            Cancel
                        </x-buttons.submit-button>
                        <x-buttons.submit-button wire:click.prevent="saveEdit" color="green" wire:loading.attr="disabled" wire:target="saveEdit"
                            x-bind:disabled="!$wire.hasUnsavedChanges()">
                            <span wire:loading.remove wire:target="saveEdit">Save Changes</span>
                            <span wire:loading.inline-flex wire:target="saveEdit" class="inline-flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Saving...
                            </span>
                        </x-buttons.submit-button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <x-modals.unsaved-confirmation show="showCancelConfirmation" title="DISCARD CHANGES?"
        message="Are you sure you want to cancel?" warning="All unsaved changes will be lost." onConfirm="cancelEdit"
        confirmText="Cancel" cancelText="Continue" />
</div>
