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
        <div class="col-span-2">
            @php
                $allLocations = $locations->pluck('location_name', 'id')->toArray();
            @endphp
            <div class="relative" x-data="{
                open: false,
                searchTerm: '',
                allOptions: @js($allLocations),
                selectedDestination: @entangle('destination_id'),
                selectedOrigin: @entangle('location_id'),
                get displayText() {
                    if (!this.selectedOrigin) return 'Select origin...';
                    const key = String(this.selectedOrigin);
                    return this.allOptions[key] || this.allOptions[Number(key)] || 'Select origin...';
                },
                get availableOptions() {
                    // Exclude selected destination
                    const filtered = {};
                    for (const [key, value] of Object.entries(this.allOptions)) {
                        if (this.selectedDestination && Number(key) === Number(this.selectedDestination)) {
                            continue;
                        }
                        filtered[key] = value;
                    }
                    return filtered;
                },
                get filteredOptions() {
                    if (!this.searchTerm) {
                        return this.availableOptions;
                    }
                    const term = this.searchTerm.toLowerCase();
                    const filtered = {};
                    for (const [key, value] of Object.entries(this.availableOptions)) {
                        if (String(value).toLowerCase().includes(term)) {
                            filtered[key] = value;
                        }
                    }
                    return filtered;
                },
                closeDropdown() {
                    this.open = false;
                    this.searchTerm = '';
                }
            }" x-ref="originDropdown" @click.outside="closeDropdown()"
                @focusin.window="
                    const target = $event.target;
                    const container = $refs.originDropdown;
                    if (!container.contains(target)) {
                        if (target.tagName === 'INPUT' || target.tagName === 'SELECT' || target.tagName === 'TEXTAREA' || (target.tagName === 'BUTTON' && target.closest('[x-data]'))) {
                            closeDropdown();
                        }
                    }
                ">
                <button type="button" x-on:click="open = !open"
                    class="inline-flex justify-between w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-blue-500"
                    :class="{ 'ring-2 ring-blue-500': open }">
                    <span :class="{ 'text-gray-400': !selectedOrigin }">
                        <span x-text="displayText"></span>
                    </span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 ml-2 -mr-1 transition-transform"
                        :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M6.293 9.293a1 1 0 011.414 0L10 11.586l2.293-2.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
                <div x-show="open" x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="absolute right-0 mt-2 w-full rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 p-1 space-y-1"
                    style="display: none; z-index: 9999;" x-cloak @click.stop>
                    <input type="text" x-model="searchTerm" x-on:keydown.escape="closeDropdown()"
                        class="block w-full px-4 py-2 text-gray-800 border rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Search locations..." autocomplete="off">
                    <div class="overflow-y-auto" style="max-height: 200px;">
                        <template x-for="[value, label] in Object.entries(filteredOptions)" :key="value">
                            <a href="#"
                                x-on:click.prevent="
                                    $wire.set('location_id', Number(value));
                                    closeDropdown();
                                "
                                class="block px-4 py-2 text-gray-700 hover:bg-gray-100 active:bg-blue-100 cursor-pointer rounded-md transition-colors"
                                :class="$wire.get('location_id') == Number(value) ? 'bg-blue-50 text-blue-700' : ''">
                                <span x-text="label"></span>
                            </a>
                        </template>
                        <div x-show="Object.keys(filteredOptions).length === 0"
                            class="px-4 py-6 text-center text-sm text-gray-500" style="display: none;">
                            No results found
                        </div>
                    </div>
                </div>
            </div>
            @error('location_id')
                <span class="text-red-500 text-xs">{{ $message }}</span>
            @enderror
        </div>
    </div>

    {{-- Destination --}}
    <div class="grid grid-cols-3 mb-4">
        <div class="font-semibold text-gray-700">Destination:<span class="text-red-500">*</span></div>
        <div class="col-span-2">
            @php
                $allLocations = $locations->pluck('location_name', 'id')->toArray();
            @endphp
            <div class="relative" x-data="{
                open: false,
                searchTerm: '',
                allOptions: @js($allLocations),
                selectedOrigin: @entangle('location_id'),
                selectedDestination: @entangle('destination_id'),
                get displayText() {
                    if (!this.selectedDestination) return 'Select destination...';
                    const key = String(this.selectedDestination);
                    return this.allOptions[key] || this.allOptions[Number(key)] || 'Select destination...';
                },
                get availableOptions() {
                    // Exclude selected origin
                    const filtered = {};
                    for (const [key, value] of Object.entries(this.allOptions)) {
                        if (this.selectedOrigin && Number(key) === Number(this.selectedOrigin)) {
                            continue;
                        }
                        filtered[key] = value;
                    }
                    return filtered;
                },
                get filteredOptions() {
                    if (!this.searchTerm) {
                        return this.availableOptions;
                    }
                    const term = this.searchTerm.toLowerCase();
                    const filtered = {};
                    for (const [key, value] of Object.entries(this.availableOptions)) {
                        if (String(value).toLowerCase().includes(term)) {
                            filtered[key] = value;
                        }
                    }
                    return filtered;
                },
                closeDropdown() {
                    this.open = false;
                    this.searchTerm = '';
                }
            }" x-ref="destinationDropdown" @click.outside="closeDropdown()"
                @focusin.window="
                    const target = $event.target;
                    const container = $refs.destinationDropdown;
                    if (!container.contains(target)) {
                        if (target.tagName === 'INPUT' || target.tagName === 'SELECT' || target.tagName === 'TEXTAREA' || (target.tagName === 'BUTTON' && target.closest('[x-data]'))) {
                            closeDropdown();
                        }
                    }
                ">
                <button type="button" x-on:click="open = !open"
                    class="inline-flex justify-between w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-blue-500"
                    :class="{ 'ring-2 ring-blue-500': open }">
                    <span :class="{ 'text-gray-400': !selectedDestination }">
                        <span x-text="displayText"></span>
                    </span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 ml-2 -mr-1 transition-transform"
                        :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M6.293 9.293a1 1 0 011.414 0L10 11.586l2.293-2.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
                <div x-show="open" x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="absolute right-0 mt-2 w-full rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 p-1 space-y-1"
                    style="display: none; z-index: 9999;" x-cloak @click.stop>
                    <input type="text" x-model="searchTerm" x-on:keydown.escape="closeDropdown()"
                        class="block w-full px-4 py-2 text-gray-800 border rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Search locations..." autocomplete="off">
                    <div class="overflow-y-auto" style="max-height: 200px;">
                        <template x-for="[value, label] in Object.entries(filteredOptions)" :key="value">
                            <a href="#"
                                x-on:click.prevent="
                                    $wire.set('destination_id', Number(value));
                                    closeDropdown();
                                "
                                class="block px-4 py-2 text-gray-700 hover:bg-gray-100 active:bg-blue-100 cursor-pointer rounded-md transition-colors"
                                :class="$wire.get('destination_id') == Number(value) ? 'bg-blue-50 text-blue-700' : ''">
                                <span x-text="label"></span>
                            </a>
                        </template>
                        <div x-show="Object.keys(filteredOptions).length === 0"
                            class="px-4 py-6 text-center text-sm text-gray-500" style="display: none;">
                            No results found
                        </div>
                    </div>
                </div>
            </div>
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

    {{-- Receiving Guard (Optional) --}}
    <div class="grid grid-cols-3 mb-4">
        <div class="font-semibold text-gray-700">
            Receiving Guard:
            <span class="float-right" x-data="{ receivedGuardId: @entangle('received_guard_id') }">
                <button type="button" x-show="receivedGuardId" wire:click="$set('received_guard_id', null)"
                    class="text-xs text-blue-600 hover:text-blue-800 font-medium" style="display: none;">
                    Clear
                </button>
            </span>
        </div>
        <div class="col-span-2">
            <x-forms.searchable-dropdown wire-model="received_guard_id" :options="$createReceivedGuardOptions"
                search-property="searchReceivedGuard" placeholder="Select receiving guard..."
                search-placeholder="Search guards..." />
            @error('received_guard_id')
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
