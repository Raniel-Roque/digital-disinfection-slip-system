@props([
    'slipStatus' => null,
    'editStatus' => null,
    'selectedSlip' => null,
])

@php
    // Use editStatus if available, otherwise fall back to slipStatus
    $status = $editStatus ?? $slipStatus ?? null;
    // Status: 0 = Pending, 1 = Disinfecting, 2 = In-Transit, 3 = Completed, 4 = Incomplete
    
    // Header class based on status
    $headerClass = '';
    if ($status == 0) {
        $headerClass = 'border-t-4 border-t-gray-500 bg-gray-50';      // Pending - Neutral
    } elseif ($status == 1) {
        $headerClass = 'border-t-4 border-t-blue-500 bg-blue-50';     // Disinfecting - In Progress
    } elseif ($status == 2) {
        $headerClass = 'border-t-4 border-t-yellow-500 bg-yellow-50';  // In-Transit - Transit State
    } elseif ($status == 3) {
        $headerClass = 'border-t-4 border-t-green-500 bg-green-50';    // Completed - Success
    } elseif ($status == 4) {
        $headerClass = 'border-t-4 border-t-red-500 bg-red-50';        // Incomplete - Issue State
    }
@endphp

{{-- ADMIN EDIT MODAL --}}
<x-modals.modal-template show="showEditModal" max-width="max-w-3xl" header-class="{{ $headerClass }}">
    <x-slot name="titleSlot">
        {{ strtoupper($selectedSlip?->location?->location_name . ' DISINFECTION SLIP DETAILS') }}
    </x-slot>

    @if ($selectedSlip)

        {{-- Sub Header --}}
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

        {{-- Body Fields --}}
        <div class="space-y-0 -mx-6">
            {{-- Status --}}
            <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-white">
                <div class="font-semibold text-gray-500">Status:<span class="text-red-500">*</span></div>
                <div class="text-gray-900">
                    <select wire:model="editStatus"
                        class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-blue-500 hover:cursor-pointer cursor-pointer">
                        <option value="0">Pending</option>
                        <option value="1">Disinfecting</option>
                        <option value="2">In-Transit</option>
                        <option value="3">Completed</option>
                        <option value="4">Incomplete</option>
                    </select>
                    @error('editStatus')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            {{-- Vehicle --}}
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

        {{-- Driver --}}
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

            {{-- Origin --}}
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

            {{-- Destination --}}
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

            {{-- Reason --}}
            <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs @if (($status == 3 || $status == 4) && $selectedSlip->completed_at) bg-white @else bg-gray-100 @endif">
                <div class="font-semibold text-gray-500">Reason:<span class="text-red-500">*</span></div>
                <div class="text-gray-900">
                    <x-forms.searchable-dropdown-paginated wire-model="editReasonId" data-method="getPaginatedReasons"
                        search-property="searchEditReason" placeholder="Select reason..."
                        search-placeholder="Search reasons..." :per-page="20" />
                    @error('editReasonId')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            {{-- Completion Date (only when completed or incomplete) --}}
            @if (($status == 3 || $status == 4) && $selectedSlip->completed_at)
                <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-gray-100">
                    <div class="font-semibold text-gray-500">End Date:</div>
                    <div class="text-gray-900">
                        {{ \Carbon\Carbon::parse($selectedSlip->completed_at)->format('M d, Y - h:i A') }}
                    </div>
                </div>
            @endif

            {{-- Remarks --}}
            <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs @if (($status == 3 || $status == 4) && $selectedSlip->completed_at) bg-white @else bg-gray-100 @endif">
                <div class="font-semibold text-gray-500">Remarks:</div>
                <div class="text-gray-900 wrap-break-words min-w-0" style="word-break: break-word; overflow-wrap: break-word;">
                    <textarea wire:model="editRemarksForDisinfection" class="w-full border rounded px-2 py-2 text-sm" rows="6"></textarea>
                    @error('editRemarksForDisinfection')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Sub Footer --}}
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
                            @if ($status == 3)
                            <span class="text-red-500">*</span>
                        @endif
                        </div>
                        @if ($status == 0 || $status == 1 || $status == 2)
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
    @else
        <p class="text-gray-500 text-center">No details available.</p>
    @endif

    {{-- Footer --}}
    <x-slot name="footer">
        <div class="flex justify-between w-full gap-2">
            <div>
                {{-- Delete Button --}}
                @if ($this->canDelete())
                    <x-buttons.submit-button wire:click="confirmDeleteSlip" color="red">
                        Delete
                    </x-buttons.submit-button>
                @endif
            </div>
            <div class="flex gap-2">
                <x-buttons.submit-button wire:click="closeEditModal" color="white">
                    Cancel
                </x-buttons.submit-button>

                <x-buttons.submit-button wire:click.prevent="checkBeforeSave" color="green" wire:loading.attr="disabled" wire:target="saveEdit"
                    x-bind:disabled="!$wire.hasChanges">
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
    </x-slot>

</x-modals.modal-template>

{{-- Cancel Confirmation Modal --}}
<x-modals.unsaved-confirmation show="showCancelEditConfirmation" title="DISCARD CHANGES?"
    message="Are you sure you want to cancel?" warning="All unsaved changes will be lost." onConfirm="cancelEdit"
    confirmText="Cancel" cancelText="Continue" />

{{-- Final Status Confirmation Modal --}}
@if ($selectedSlip)
<x-modals.modal-template show="showFinalStatusConfirmation" max-width="max-w-md">
    <x-slot name="titleSlot">
        <div class="flex items-center gap-2">
            <svg class="w-6 h-6 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <span class="text-red-600 font-bold">FINAL STATUS WARNING</span>
        </div>
    </x-slot>

    <div class="p-6">
        <div class="mb-4">
            <p class="text-gray-700 mb-3">
                You are about to save this slip as <span class="font-bold text-red-600">{{ $editStatus == 3 ? 'COMPLETED' : 'INCOMPLETE' }}</span>.
            </p>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-3">
                <p class="text-sm text-red-800">
                    <span class="font-bold">⚠️ This action is FINAL and IRREVERSIBLE.</span>
                </p>
                <p class="text-sm text-red-700 mt-2">
                    Once saved, this slip <span class="font-semibold">cannot be edited or modified</span> by admins anymore.
                    Only super admins will have access to edit completed or incomplete slips.
                </p>
            </div>
            <p class="text-gray-700">
                Are you absolutely sure all information is correct?
            </p>
        </div>
    </div>

    <x-slot name="footer">
        <div class="flex justify-end w-full gap-2">
            <x-buttons.submit-button wire:click="$set('showFinalStatusConfirmation', false)" color="white">
                Go Back
            </x-buttons.submit-button>
            <x-buttons.submit-button wire:click="saveEdit" color="red" wire:loading.attr="disabled" wire:target="saveEdit">
                <span wire:loading.remove wire:target="saveEdit">Yes, Save as {{ $editStatus == 3 ? 'Completed' : 'Incomplete' }}</span>
                <span wire:loading.inline-flex wire:target="saveEdit" class="inline-flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Saving...
                </span>
            </x-buttons.submit-button>
        </div>
    </x-slot>
</x-modals.modal-template>
@endif
