@props([
    'trucks' => collect(),
    'locations' => collect(),
    'drivers' => collect(),
    'guards' => collect(),
    'availableOriginsOptions' => [],
    'availableDestinationsOptions' => [],
    'editTruckOptions' => [],
    'editDriverOptions' => [],
    'editGuardOptions' => [],
    'editReceivedGuardOptions' => [],
    'slipStatus' => null,
    'editStatus' => null,
    'selectedSlip' => null,
])

@php
    // Use editStatus if available, otherwise fall back to slipStatus
    $status = $editStatus ?? $slipStatus ?? null;
    // Status: 0 = Ongoing, 1 = Disinfecting, 2 = Completed
    
    // Header class based on status
    $headerClass = '';
    if ($status == 0) {
        $headerClass = 'border-t-4 border-t-red-500 bg-red-50';
    } elseif ($status == 1) {
        $headerClass = 'border-t-4 border-t-orange-500 bg-orange-50';
    } elseif ($status == 2) {
        $headerClass = 'border-t-4 border-t-green-500 bg-green-50';
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
                    <select wire:model.live="editStatus"
                        class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-blue-500 hover:cursor-pointer cursor-pointer">
                        <option value="0">Ongoing</option>
                        <option value="1">Disinfecting</option>
                        <option value="2">Completed</option>
                    </select>
                    @error('editStatus')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            {{-- Plate No --}}
            <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-gray-100">
                <div class="font-semibold text-gray-500">Plate No:<span class="text-red-500">*</span></div>
                <div class="text-gray-900">
                    <x-forms.searchable-dropdown wire-model="editTruckId" :options="$editTruckOptions" search-property="searchEditTruck"
                        placeholder="Select plate number..." search-placeholder="Search plates..." />
                    @error('editTruckId')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
            </div>

        {{-- Driver --}}
            <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-white">
                <div class="font-semibold text-gray-500">Driver:<span class="text-red-500">*</span></div>
                <div class="text-gray-900">
                    <x-forms.searchable-dropdown wire-model="editDriverId" :options="$editDriverOptions"
                        search-property="searchEditDriver" placeholder="Select driver..."
                        search-placeholder="Search drivers..." />
                    @error('editDriverId')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            {{-- Origin --}}
            <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-gray-100">
                <div class="font-semibold text-gray-500">Origin:<span class="text-red-500">*</span></div>
                <div class="text-gray-900">
                    @php
                        $allLocations = $locations->pluck('location_name', 'id')->toArray();
                    @endphp
                    <div class="relative" x-data="{
                        open: false,
                        searchTerm: '',
                        allOptions: @js($allLocations),
                        selectedDestination: @entangle('editDestinationId'),
                        selectedOrigin: @entangle('editLocationId'),
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
                    }" x-ref="editOriginDropdown" @click.outside="closeDropdown()"
                        @focusin.window="
                                const target = $event.target;
                                const container = $refs.editOriginDropdown;
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
                            <svg xmlns="https://www.w3.org/2000/svg" class="w-5 h-5 ml-2 -mr-1 transition-transform"
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
                                                $wire.set('editLocationId', Number(value));
                                                closeDropdown();
                                            "
                                        class="block px-4 py-2 text-gray-700 hover:bg-gray-100 active:bg-blue-100 cursor-pointer rounded-md transition-colors"
                                        :class="$wire.get('editLocationId') == Number(value) ? 'bg-blue-50 text-blue-700' : ''">
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
                    @error('editLocationId')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            {{-- Destination --}}
            <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-white">
                <div class="font-semibold text-gray-500">Destination:<span class="text-red-500">*</span></div>
                <div class="text-gray-900">
                    @php
                        $allLocations = $locations->pluck('location_name', 'id')->toArray();
                    @endphp
                    <div class="relative" x-data="{
                        open: false,
                        searchTerm: '',
                        allOptions: @js($allLocations),
                        selectedOrigin: @entangle('editLocationId'),
                        selectedDestination: @entangle('editDestinationId'),
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
                    }" x-ref="editDestinationDropdown"
                        @click.outside="closeDropdown()"
                        @focusin.window="
                            const target = $event.target;
                            const container = $refs.editDestinationDropdown;
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
                            <svg xmlns="https://www.w3.org/2000/svg" class="w-5 h-5 ml-2 -mr-1 transition-transform"
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
                                            $wire.set('editDestinationId', Number(value));
                                            closeDropdown();
                                        "
                                        class="block px-4 py-2 text-gray-700 hover:bg-gray-100 active:bg-blue-100 cursor-pointer rounded-md transition-colors"
                                        :class="$wire.get('editDestinationId') == Number(value) ? 'bg-blue-50 text-blue-700' : ''">
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
                    @error('editDestinationId')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            {{-- Completion Date (only when completed) --}}
            @if ($status == 2 && $selectedSlip->completed_at)
                <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-gray-100">
                    <div class="font-semibold text-gray-500">End Date:</div>
                    <div class="text-gray-900">
                        {{ \Carbon\Carbon::parse($selectedSlip->completed_at)->format('M d, Y - h:i A') }}
                    </div>
                </div>
            @endif

            {{-- Reason --}}
            <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs @if ($status == 2 && $selectedSlip->completed_at) bg-white @else bg-gray-100 @endif">
                <div class="font-semibold text-gray-500">Reason:</div>
                <div class="text-gray-900 wrap-break-words min-w-0" style="word-break: break-word; overflow-wrap: break-word;">
                    <textarea wire:model.live="editReasonForDisinfection" class="w-full border rounded px-2 py-2 text-sm" rows="6"></textarea>
                    @error('editReasonForDisinfection')
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
                        <x-forms.searchable-dropdown wire-model="editHatcheryGuardId" :options="$editGuardOptions"
                            search-property="searchEditHatcheryGuard" placeholder="Select hatchery guard..."
                            search-placeholder="Search guards..." />
                        @error('editHatcheryGuardId')
                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div>
                    <div class="font-semibold text-gray-500 mb-0.5">
                        Received By:
                        @if ($status == 1 || $status == 2)
                            <span class="text-red-500">*</span>
                        @endif
                        @if ($status == 0)
                            <span class="float-right" x-data="{ editReceivedGuardId: @entangle('editReceivedGuardId') }">
                                <button type="button" x-show="editReceivedGuardId"
                                    wire:click="$set('editReceivedGuardId', null)"
                                    class="text-xs text-blue-600 hover:text-blue-800 font-medium" style="display: none;">
                                    Clear
                                </button>
                            </span>
                        @endif
                    </div>
                    <div class="text-gray-900">
                        <x-forms.searchable-dropdown wire-model="editReceivedGuardId" :options="$editReceivedGuardOptions"
                            search-property="searchEditReceivedGuard" placeholder="Select receiving guard..."
                            search-placeholder="Search guards..." />
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
                    <x-buttons.submit-button wire:click="$set('showSlipDeleteConfirmation', true)" color="red">
                        Delete
                    </x-buttons.submit-button>
                @endif
            </div>
            <div class="flex gap-2">
                <x-buttons.submit-button wire:click="closeEditModal" color="white">
                    Cancel
                </x-buttons.submit-button>

                <x-buttons.submit-button wire:click.prevent="saveEdit" color="green" wire:loading.attr="disabled" wire:target="saveEdit"
                    x-bind:disabled="!$wire.hasChanges">
                    <span wire:loading.remove wire:target="saveEdit">Save Changes</span>
                    <span wire:loading wire:target="saveEdit" class="inline-flex items-center gap-2">
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
