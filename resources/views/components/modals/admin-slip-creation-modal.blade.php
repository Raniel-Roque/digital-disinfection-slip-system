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
])

{{-- ADMIN CREATE MODAL --}}
<x-modals.modal-template show="showCreateModal" title="CREATE NEW DISINFECTION SLIP" max-width="max-w-3xl">

    {{-- Plate Number --}}
    <div class="grid grid-cols-3 mb-4">
        <div class="font-semibold text-gray-700">Plate No:<span class="text-red-500">*</span></div>
        <div class="col-span-2">
            <x-forms.searchable-dropdown wire-model="truck_id" :options="$createTruckOptions" search-property="searchTruck"
                placeholder="Select plate number..." search-placeholder="Search plates..." />
            @error('truck_id')
                <span class="text-red-500 text-xs">{{ $message }}</span>
            @enderror
        </div>
    </div>

    {{-- Origin --}}
    <div class="grid grid-cols-3 mb-4">
        <div class="font-semibold text-gray-700">Origin:<span class="text-red-500">*</span></div>
        <div class="col-span-2" wire:key="origin-wrapper-{{ md5(json_encode($availableOriginsOptions)) }}">
            <x-forms.searchable-dropdown wire-model="location_id" :options="$availableOriginsOptions" search-property="searchOrigin"
                placeholder="Select origin..." search-placeholder="Search locations..." />
            @error('location_id')
                <span class="text-red-500 text-xs">{{ $message }}</span>
            @enderror
        </div>
    </div>

    {{-- Destination --}}
    <div class="grid grid-cols-3 mb-4">
        <div class="font-semibold text-gray-700">Destination:<span class="text-red-500">*</span></div>
        <div class="col-span-2" wire:key="destination-wrapper-{{ md5(json_encode($availableDestinationsOptions)) }}">
            <x-forms.searchable-dropdown wire-model="destination_id" :options="$availableDestinationsOptions"
                search-property="searchDestination" placeholder="Select destination..."
                search-placeholder="Search locations..." />
            @error('destination_id')
                <span class="text-red-500 text-xs">{{ $message }}</span>
            @enderror
        </div>
    </div>

    {{-- Driver Name --}}
    <div class="grid grid-cols-3 mb-4">
        <div class="font-semibold text-gray-700">Driver Name:<span class="text-red-500">*</span></div>
        <div class="col-span-2">
            <x-forms.searchable-dropdown wire-model="driver_id" :options="$createDriverOptions" search-property="searchDriver"
                placeholder="Select driver..." search-placeholder="Search drivers..." />
            @error('driver_id')
                <span class="text-red-500 text-xs">{{ $message }}</span>
            @enderror
        </div>
    </div>

    {{-- Hatchery Guard --}}
    <div class="grid grid-cols-3 mb-4">
        <div class="font-semibold text-gray-700">Hatchery Guard:<span class="text-red-500">*</span></div>
        <div class="col-span-2">
            <x-forms.searchable-dropdown wire-model="hatchery_guard_id" :options="$createGuardOptions"
                search-property="searchHatcheryGuard" placeholder="Select hatchery guard..."
                search-placeholder="Search guards..." />
            @error('hatchery_guard_id')
                <span class="text-red-500 text-xs">{{ $message }}</span>
            @enderror
        </div>
    </div>

    {{-- Reason for Disinfection --}}
    <div class="grid grid-cols-3 mb-4">
        <div class="font-semibold text-gray-700">Reason:</div>
        <div class="col-span-2">
            <textarea wire:model="reason_for_disinfection"
                class="w-full border rounded px-2 py-1 text-sm border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                rows="6" placeholder="Enter reason for disinfection..."></textarea>
            @error('reason_for_disinfection')
                <span class="text-red-500 text-xs">{{ $message }}</span>
            @enderror
        </div>
    </div>

    {{-- Footer --}}
    <x-slot name="footer">
        <x-buttons.submit-button wire:click="closeCreateModal" color="white">
            Cancel
        </x-buttons.submit-button>

        <x-buttons.submit-button wire:click="createSlip" color="blue">
            Create Slip
        </x-buttons.submit-button>
    </x-slot>

</x-modals.modal-template>

{{-- Cancel Confirmation Modal --}}
<x-modals.unsaved-confirmation show="showCancelCreateConfirmation" title="DISCARD CHANGES?"
    message="Are you sure you want to cancel?" warning="All unsaved changes will be lost." onConfirm="cancelCreate"
    confirmText="Yes, Discard Changes" cancelText="Continue Editing" />
