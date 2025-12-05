@props([
    'availableStatuses' => [],
    'locations' => collect(),
    'drivers' => collect(),
    'trucks' => collect(),
])

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    {{-- Status Filter (full width) --}}
    <div class="md:col-span-2" x-data="{
        open: false,
        selected: @entangle('filterStatus'),
        options: @js($availableStatuses),
        placeholder: 'All Statuses',
        get displayText() {
            if (this.selected !== '' && this.options[this.selected]) {
                return this.options[this.selected];
            }
            return this.placeholder;
        }
    }">
        <div class="flex items-center justify-between mb-1">
            <label class="block text-sm font-medium text-gray-700">Status</label>
            <button type="button" @click="selected = ''" x-show="selected !== ''"
                class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                Clear
            </button>
        </div>

        <div class="relative">
            <button type="button" @click="open = !open"
                class="inline-flex justify-between w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-blue-500"
                :class="{ 'ring-2 ring-blue-500': open }">
                <span x-text="displayText" :class="{ 'text-gray-400': selected === '' }"></span>
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 ml-2 -mr-1 transition-transform"
                    :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd"
                        d="M6.293 9.293a1 1 0 011.414 0L10 11.586l2.293-2.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                        clip-rule="evenodd" />
                </svg>
            </button>

            <div x-show="open" @click.outside="open = false" x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute right-0 mt-2 w-full rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 p-1 space-y-1 z-50"
                style="display: none;">
                <a href="#" @click.prevent="selected = ''; open = false"
                    class="block px-4 py-2 text-gray-700 hover:bg-gray-100 active:bg-blue-100 cursor-pointer rounded-md"
                    :class="{ 'bg-blue-50 text-blue-700': selected === '' }">
                    <span>All Statuses</span>
                </a>
                <template x-for="[value, label] in Object.entries(options)" :key="value">
                    <a href="#" @click.prevent="selected = value; open = false"
                        class="block px-4 py-2 text-gray-700 hover:bg-gray-100 active:bg-blue-100 cursor-pointer rounded-md"
                        :class="{ 'bg-blue-50 text-blue-700': selected === value }">
                        <span x-text="label"></span>
                    </a>
                </template>
            </div>
        </div>
    </div>

    {{-- Plate Number Filter --}}
    <div>
        @php
            $truckOptions = $trucks->pluck('plate_number', 'id')->toArray();
        @endphp
        <div class="flex items-center justify-between mb-1">
            <label class="block text-sm font-medium text-gray-700">Plate Number</label>
            <button type="button" wire:click="$set('filterPlateNumber', [])"
                @if (empty($filterPlateNumber)) style="display: none;" @endif
                class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                Clear
            </button>
        </div>
        <x-forms.searchable-dropdown wireModel="filterPlateNumber" :options="$truckOptions" placeholder="Select plate no..."
            searchPlaceholder="Search plate numbers..." :multiple="true" />
    </div>

    {{-- Driver Filter --}}
    <div>
        @php
            $driverOptions = $drivers
                ->mapWithKeys(function ($driver) {
                    return [$driver->id => $driver->first_name . ' ' . $driver->last_name];
                })
                ->toArray();
        @endphp
        <div class="flex items-center justify-between mb-1">
            <label class="block text-sm font-medium text-gray-700">Driver</label>
            <button type="button" wire:click="$set('filterDriver', [])"
                @if (empty($filterDriver)) style="display: none;" @endif
                class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                Clear
            </button>
        </div>
        <x-forms.searchable-dropdown wireModel="filterDriver" :options="$driverOptions" placeholder="Select drivers..."
            searchPlaceholder="Search drivers..." :multiple="true" />
    </div>

    {{-- Origin Filter --}}
    <div>
        <div class="flex items-center justify-between mb-1">
            <label class="block text-sm font-medium text-gray-700">Origin</label>
            <button type="button" wire:click="$set('filterOrigin', [])"
                @if (empty($filterOrigin)) style="display: none;" @endif
                class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                Clear
            </button>
        </div>
        <x-forms.searchable-dropdown wireModel="filterOrigin" :options="$locations->pluck('location_name', 'id')->toArray()" placeholder="Select origin..."
            searchPlaceholder="Search origin..." :multiple="true" />
    </div>

    {{-- Destination Filter --}}
    <div>
        <div class="flex items-center justify-between mb-1">
            <label class="block text-sm font-medium text-gray-700">Destination</label>
            <button type="button" wire:click="$set('filterDestination', [])"
                @if (empty($filterDestination)) style="display: none;" @endif
                class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                Clear
            </button>
        </div>
        <x-forms.searchable-dropdown wireModel="filterDestination" :options="$locations->pluck('location_name', 'id')->toArray()" placeholder="Select destination..."
            searchPlaceholder="Search destinations..." :multiple="true" />
    </div>

    {{-- From Date Input --}}
    <div x-data="{ toDate: @entangle('filterCreatedTo') }">
        <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
        <input type="date" wire:model="filterCreatedFrom" :max="toDate || '{{ date('Y-m-d') }}'"
            class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-blue-500">
    </div>

    {{-- To Date Input --}}
    <div x-data="{ fromDate: @entangle('filterCreatedFrom') }">
        <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
        <input type="date" wire:model="filterCreatedTo" :min="fromDate" max="{{ date('Y-m-d') }}"
            class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-blue-500">
    </div>

</div>
