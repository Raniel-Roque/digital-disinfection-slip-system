@props([
    'availableStatuses' => [],
    'filterSortDirection' => null,
])

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    {{-- Report Type Filter (full width) --}}
    <div class="md:col-span-2" x-data="{
        open: false,
        options: { 'slip': 'Slip', 'misc': 'Miscellaneous' },
        selected: @entangle('filterReportType').live,
        placeholder: 'Select report type',
        get displayText() {
            if (this.selected === null || this.selected === undefined || this.selected === '') {
                return this.placeholder;
            }
            return this.options[this.selected] || this.placeholder;
        },
        closeDropdown() {
            this.open = false;
        },
        handleFocusIn(event) {
            const target = event.target;
            const container = $refs.typeDropdownContainer;
            if (this.open && !container.contains(target)) {
                if (target.tagName === 'INPUT' ||
                    target.tagName === 'SELECT' ||
                    target.tagName === 'TEXTAREA' ||
                    (target.tagName === 'BUTTON' && target.closest('[x-data]') && !container.contains(target.closest('[x-data]')))) {
                    this.closeDropdown();
                }
            }
        }
    }" x-ref="typeDropdownContainer" @click.outside="closeDropdown()"
        @focusin.window="handleFocusIn($event)">
        <div class="flex items-center justify-between mb-1">
            <label class="block text-sm font-medium text-gray-700">Report Type</label>
            <button type="button" wire:click="$set('filterReportType', null)"
                x-show="selected !== null && selected !== undefined && selected !== ''"
                class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                Clear
            </button>
        </div>

        <div class="relative">
            <button type="button" x-on:click="open = !open"
                class="inline-flex justify-between w-full px-4 py-2 text-sm font-medium bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-blue-500"
                :class="{ 'ring-2 ring-blue-500': open }">
                <span :class="{ 'text-gray-400': selected === null || selected === undefined || selected === '' }">
                    <span x-text="displayText"></span>
                </span>
                <svg xmlns="https://www.w3.org/2000/svg" class="w-5 h-5 ml-2 -mr-1 transition-transform"
                    :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd"
                        d="M6.293 9.293a1 1 0 011.414 0L10 11.586l2.293-2.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                        clip-rule="evenodd" />
                </svg>
            </button>

            <!-- Dropdown Menu -->
            <div x-show="open" x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute right-0 mt-2 w-full rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 p-1 space-y-1 z-50"
                style="display: none;" x-cloak @click.stop>
                <template x-for="[value, label] in Object.entries(options)" :key="value">
                    <a href="#"
                        @click.prevent="
                            selected = value;
                            closeDropdown();
                        "
                        class="block px-4 py-2 text-gray-700 hover:bg-gray-100 active:bg-blue-100 cursor-pointer rounded-md transition-colors"
                        :class="{
                            'bg-blue-50 text-blue-700': selected !== null && selected !== undefined && selected !== '' && selected === value
                        }">
                        <span x-text="label"></span>
                    </a>
                </template>
            </div>
        </div>
    </div>

    {{-- Status Filter (full width) --}}
    <div class="md:col-span-2" x-data="{
        open: false,
        options: @js($availableStatuses),
        selected: @entangle('filterResolved').live,
        placeholder: 'Select status',
        get displayText() {
            if (this.selected === null || this.selected === undefined || this.selected === '') {
                return this.placeholder;
            }
            const key = String(this.selected);
            return this.options[key] || this.placeholder;
        },
        closeDropdown() {
            this.open = false;
        },
        handleFocusIn(event) {
            const target = event.target;
            const container = $refs.statusDropdownContainer;
            if (this.open && !container.contains(target)) {
                if (target.tagName === 'INPUT' ||
                    target.tagName === 'SELECT' ||
                    target.tagName === 'TEXTAREA' ||
                    (target.tagName === 'BUTTON' && target.closest('[x-data]') && !container.contains(target.closest('[x-data]')))) {
                    this.closeDropdown();
                }
            }
        }
    }" x-ref="statusDropdownContainer" @click.outside="closeDropdown()"
        @focusin.window="handleFocusIn($event)">
        <div class="flex items-center justify-between mb-1">
            <label class="block text-sm font-medium text-gray-700">Status</label>
            <button type="button" wire:click="$set('filterResolved', null)"
                x-show="selected !== null && selected !== undefined && selected !== ''"
                class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                Clear
            </button>
        </div>

        <div class="relative">
            <button type="button" x-on:click="open = !open"
                class="inline-flex justify-between w-full px-4 py-2 text-sm font-medium bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-blue-500"
                :class="{ 'ring-2 ring-blue-500': open }">
                <span :class="{ 'text-gray-400': selected === null || selected === undefined || selected === '' }">
                    <span x-text="displayText"></span>
                </span>
                <svg xmlns="https://www.w3.org/2000/svg" class="w-5 h-5 ml-2 -mr-1 transition-transform"
                    :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd"
                        d="M6.293 9.293a1 1 0 011.414 0L10 11.586l2.293-2.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                        clip-rule="evenodd" />
                </svg>
            </button>

            <!-- Dropdown Menu -->
            <div x-show="open" x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute right-0 mt-2 w-full rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 p-1 space-y-1 z-50"
                style="display: none;" x-cloak @click.stop>
                <template x-for="[value, label] in Object.entries(options)" :key="value">
                    <a href="#"
                        @click.prevent="
                            selected = String(value);
                            closeDropdown();
                        "
                        class="block px-4 py-2 text-gray-700 hover:bg-gray-100 active:bg-blue-100 cursor-pointer rounded-md transition-colors"
                        :class="{
                            'bg-blue-50 text-blue-700': selected !== null && selected !== undefined && selected !== '' && String(selected) === String(value)
                        }">
                        <span x-text="label"></span>
                    </a>
                </template>
            </div>
        </div>
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

{{-- Sort Section --}}
<div class="mt-4 pt-4 border-t border-gray-200">
    <label class="block text-xs font-medium text-gray-700 mb-2">Sort by Created Date</label>
    <div class="flex gap-2">
        <button wire:click="$set('filterSortDirection', 'asc')" type="button"
            class="flex-1 inline-flex items-center justify-center px-3 py-1.5 text-sm font-medium rounded-lg border transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2
                @if ($filterSortDirection === 'asc') bg-green-50 border-green-500 text-green-700 hover:bg-green-100 focus:ring-green-500
                @else bg-white border-gray-300 text-gray-700 hover:bg-gray-50 focus:ring-gray-500
                @endif">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
            </svg>
            Asc
        </button>
        <button wire:click="$set('filterSortDirection', 'desc')" type="button"
            class="flex-1 inline-flex items-center justify-center px-3 py-1.5 text-sm font-medium rounded-lg border transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2
                @if ($filterSortDirection === 'desc' || $filterSortDirection === null) bg-red-50 border-red-500 text-red-700 hover:bg-red-100 focus:ring-red-500
                @else bg-white border-gray-300 text-gray-700 hover:bg-gray-50 focus:ring-gray-500
                @endif">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
            Desc
        </button>
    </div>
</div>

