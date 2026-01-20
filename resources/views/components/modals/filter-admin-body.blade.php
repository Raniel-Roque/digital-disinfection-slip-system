@props([
    'availableStatuses' => [],
    'filterStatus' => null,
])

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    {{-- Status Filter using shared component --}}
    <x-filters.status-dropdown 
        label="Status"
        wireModel="filterStatus"
        :options="$availableStatuses"
        placeholder="Select status"
        :fullWidth="true"
    />

    {{-- Vehicle Filter using shared component --}}
    <x-filters.entity-selector 
        label="Vehicle"
        wireModel="filterVehicle"
        dataMethod="getPaginatedVehicles"
        searchProperty="searchFilterVehicle"
        placeholder="Select vehicle..."
        searchPlaceholder="Search vehicles..."
        :multiple="true"
    />

    {{-- Driver Filter using shared component --}}
    <x-filters.entity-selector 
        label="Driver"
        wireModel="filterDriver"
        dataMethod="getPaginatedDrivers"
        searchProperty="searchFilterDriver"
        placeholder="Select drivers..."
        searchPlaceholder="Search drivers..."
        :multiple="true"
    />

    {{-- Hatchery Guard Filter using shared component --}}
    <x-filters.entity-selector 
        label="Hatchery Guard"
        wireModel="filterHatcheryGuard"
        dataMethod="getPaginatedGuards"
        searchProperty="searchFilterHatcheryGuard"
        placeholder="Select hatchery guards..."
        searchPlaceholder="Search hatchery guards..."
        :multiple="true"
    />

    {{-- Received Guard Filter using shared component --}}
    <x-filters.entity-selector 
        label="Received Guard"
        wireModel="filterReceivedGuard"
        dataMethod="getPaginatedGuards"
        searchProperty="searchFilterReceivedGuard"
        placeholder="Select received guards..."
        searchPlaceholder="Search received guards..."
        :multiple="true"
    />

    {{-- Origin Filter using shared component --}}
    <x-filters.entity-selector 
        label="Origin"
        wireModel="filterOrigin"
        dataMethod="getPaginatedLocations"
        searchProperty="searchFilterOrigin"
        placeholder="Select origin..."
        searchPlaceholder="Search origin..."
        :multiple="true"
    />

    {{-- Destination Filter using shared component --}}
    <x-filters.entity-selector 
        label="Destination"
        wireModel="filterDestination"
        dataMethod="getPaginatedLocations"
        searchProperty="searchFilterDestination"
        placeholder="Select destination..."
        searchPlaceholder="Search destinations..."
        :multiple="true"
    />

    {{-- From Date Input --}}
    <div x-data="{ filterValue: @entangle('filterCreatedFrom') }">
        <div class="flex items-center justify-between mb-1">
            <label class="block text-sm font-medium text-gray-700">From Date</label>
            <button type="button" wire:click="$set('filterCreatedFrom', null)"
                wire:target="filterCreatedFrom"
                x-show="filterValue && filterValue !== '' && filterValue !== null"
                class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                Clear
            </button>
        </div>
        <input type="date" wire:model.live="filterCreatedFrom"
            x-ref="fromDateInput"
            @input="
                const toInput = $refs.toDateInput;
                if (toInput) {
                    // Clear To Date if it's before the new From Date
                    if (toInput.value && $el.value && toInput.value < $el.value) {
                        toInput.value = '';
                        $wire.set('filterCreatedTo', '');
                    }
                }
            "
            max="<?php echo date('Y-m-d'); ?>"
            class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-blue-500">
    </div>

    {{-- To Date Input --}}
    <div x-data="{ filterValue: @entangle('filterCreatedTo') }">
        <div class="flex items-center justify-between mb-1">
            <label class="block text-sm font-medium text-gray-700">To Date</label>
            <button type="button" wire:click="$set('filterCreatedTo', null)"
                wire:target="filterCreatedTo"
                x-show="filterValue && filterValue !== '' && filterValue !== null"
                class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                Clear
            </button>
        </div>
        <input type="date" wire:model.live="filterCreatedTo"
            x-ref="toDateInput"
            @input="
                const fromInput = $refs.fromDateInput;
                if (fromInput) {
                    // Clear From Date if it exceeds the new To Date
                    if (fromInput.value && $el.value && fromInput.value > $el.value) {
                        fromInput.value = '';
                        $wire.set('filterCreatedFrom', '');
                    }
                }
            "
            max="<?php echo date('Y-m-d'); ?>"
            class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-blue-500">
    </div>

    {{-- Exclude Deleted Items Toggle (full width) - SUPERADMIN ONLY --}}
    @if (Auth::user()->user_type === 2)
        <div class="md:col-span-2 border-t border-gray-200 pt-4 mt-2">
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" wire:model="excludeDeletedItems"
                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2">
                <span class="text-sm font-medium text-gray-700">
                    Exclude slips with deleted items (vehicles, drivers, locations, guards)
                </span>
            </label>
            <p class="text-xs text-gray-500 mt-1 ml-7">
                When enabled, hides slips where any related item has been deleted
            </p>
        </div>
    @endif
</div>
