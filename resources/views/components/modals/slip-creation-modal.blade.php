@props([
    'trucks' => collect(),
    'locations' => collect(),
    'drivers' => collect(),
    'truckOptions' => [],
    'locationOptions' => [],
    'driverOptions' => [],
    'isCreating' => false,
])

{{-- CREATE MODAL --}}
<x-modals.modal-template show="showCreateModal" title="CREATE NEW DISINFECTION SLIP" max-width="max-w-3xl">

    {{-- Plate Number --}}
    <div class="grid grid-cols-3 mb-4">
        <div class="font-semibold text-gray-700">Plate No:<span class="text-red-500">*</span></div>
        <div class="col-span-2">
            <x-forms.searchable-dropdown wire-model="truck_id" :options="$truckOptions" search-property="searchTruck"
                placeholder="Select plate number..." search-placeholder="Search plates..." />
            @error('truck_id')
                <span class="text-red-500 text-xs">{{ $message }}</span>
            @enderror
        </div>
    </div>

    {{-- Destination --}}
    <div class="grid grid-cols-3 mb-4">
        <div class="font-semibold text-gray-700">Destination:<span class="text-red-500">*</span></div>
        <div class="col-span-2">
            <x-forms.searchable-dropdown wire-model="destination_id" :options="$locationOptions"
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
            <x-forms.searchable-dropdown wire-model="driver_id" :options="$driverOptions" search-property="searchDriver"
                placeholder="Select driver..." search-placeholder="Search drivers..." />
            @error('driver_id')
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
        <x-buttons.submit-button wire:click="closeCreateModal" color="white" wire:loading.attr="disabled" wire:target="createSlip">
            Cancel
        </x-buttons.submit-button>

        <x-buttons.submit-button wire:click.prevent="createSlip" color="blue" wire:loading.attr="disabled" wire:target="createSlip"
            :disabled="$isCreating">
            <span wire:loading.remove wire:target="createSlip">Create Slip</span>
            <span wire:loading wire:target="createSlip">Creating...</span>
        </x-buttons.submit-button>
    </x-slot>

</x-modals.modal-template>

{{-- Cancel Confirmation Modal --}}
<x-modals.unsaved-confirmation show="showCancelCreateConfirmation" title="DISCARD CHANGES?"
    message="Are you sure you want to cancel?" warning="All unsaved changes will be lost." onConfirm="cancelCreate"
    confirmText="Cancel" cancelText="Continue" />
