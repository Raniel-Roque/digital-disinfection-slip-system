@props([
    'trucks' => collect(),
    'locations' => collect(),
    'drivers' => collect(),
    'guards' => collect(),
    'availableOriginsOptions' => [],
    'availableDestinationsOptions' => [],
    'createTruckOptions' => [],
    'createDriverOptions' => [],
    'createGuardOptions' => [],
    'createReceivedGuardOptions' => [],
    'isCreating' => false,
])

{{-- ADMIN CREATE MODAL --}}
<x-modals.modal-template show="showCreateModal" title="CREATE NEW DISINFECTION SLIP" max-width="max-w-3xl">

    {{-- Body Fields --}}
    <div class="space-y-0 -mx-6">
    {{-- Plate Number --}}
        <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-white">
            <div class="font-semibold text-gray-500">Plate No:<span class="text-red-500">*</span></div>
            <div class="text-gray-900">
            <x-forms.searchable-dropdown wire-model="truck_id" :options="$createTruckOptions" search-property="searchTruck"
                placeholder="Select plate number..." search-placeholder="Search plates..." />
            @error('truck_id')
                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
            @enderror
        </div>
    </div>

    {{-- Origin --}}
        <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-gray-100">
            <div class="font-semibold text-gray-500">Origin:<span class="text-red-500">*</span></div>
            <div class="text-gray-900">
                <x-forms.searchable-dropdown wire-model="location_id" :options="$availableOriginsOptions" search-property="searchOrigin"
                    placeholder="Select origin..." search-placeholder="Search locations..." />
                @error('location_id')
                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
            @enderror
        </div>
    </div>

    {{-- Destination --}}
        <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-white">
            <div class="font-semibold text-gray-500">Destination:<span class="text-red-500">*</span></div>
            <div class="text-gray-900">
                <x-forms.searchable-dropdown wire-model="destination_id" :options="$availableDestinationsOptions" search-property="searchDestination"
                    placeholder="Select destination..." search-placeholder="Search locations..." />
                @error('destination_id')
                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
            @enderror
        </div>
    </div>

    {{-- Driver Name --}}
        <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-gray-100">
            <div class="font-semibold text-gray-500">Driver Name:<span class="text-red-500">*</span></div>
            <div class="text-gray-900">
            <x-forms.searchable-dropdown wire-model="driver_id" :options="$createDriverOptions" search-property="searchDriver"
                placeholder="Select driver..." search-placeholder="Search drivers..." />
            @error('driver_id')
                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
            @enderror
        </div>
    </div>

    {{-- Hatchery Guard --}}
        <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-white">
            <div class="font-semibold text-gray-500">Hatchery Guard:<span class="text-red-500">*</span></div>
            <div class="text-gray-900">
            <x-forms.searchable-dropdown wire-model="hatchery_guard_id" :options="$createGuardOptions"
                search-property="searchHatcheryGuard" placeholder="Select hatchery guard..."
                search-placeholder="Search guards..." />
            @error('hatchery_guard_id')
                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
            @enderror
        </div>
    </div>

    {{-- Receiving Guard (Optional) --}}
        <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-gray-100">
            <div class="font-semibold text-gray-500">Receiving Guard:</div>
            <div class="text-gray-900">
            <x-forms.searchable-dropdown wire-model="received_guard_id" :options="$createReceivedGuardOptions"
                search-property="searchReceivedGuard" placeholder="Select receiving guard..."
                search-placeholder="Search guards..." />
            @error('received_guard_id')
                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
            @enderror
        </div>
    </div>

    {{-- Reason for Disinfection --}}
        <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-white">
            <div class="font-semibold text-gray-500">Reason:</div>
            <div class="text-gray-900">
            <textarea wire:model="reason_for_disinfection"
                class="w-full border rounded px-2 py-1 text-sm border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                rows="6" placeholder="Enter reason for disinfection..."></textarea>
            @error('reason_for_disinfection')
                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
            @enderror
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <x-slot name="footer">
        <div x-data="{ isCreating: @js($isCreating) }" class="flex justify-end gap-2">
        <x-buttons.submit-button wire:click="closeCreateModal" color="white" wire:loading.attr="disabled" wire:target="createSlip">
            Cancel
        </x-buttons.submit-button>

        <x-buttons.submit-button wire:click.prevent="createSlip" color="blue" wire:loading.attr="disabled" wire:target="createSlip"
                x-bind:disabled="isCreating">
            <span wire:loading.remove wire:target="createSlip">Create Slip</span>
            <span wire:loading.inline-flex wire:target="createSlip" class="inline-flex items-center gap-2">
                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Creating...
            </span>
        </x-buttons.submit-button>
        </div>
    </x-slot>

</x-modals.modal-template>

{{-- Cancel Confirmation Modal --}}
<x-modals.unsaved-confirmation show="showCancelCreateConfirmation" title="DISCARD CHANGES?"
    message="Are you sure you want to cancel?" warning="All unsaved changes will be lost." onConfirm="cancelCreate"
    confirmText="Discard" cancelText="Back" />
