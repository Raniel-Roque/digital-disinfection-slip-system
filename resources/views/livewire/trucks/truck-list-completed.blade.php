<div class="max-w-full bg-white border border-gray-200 rounded-xl shadow-sm p-4 m-4 pb-16 sm:pb-4">

    {{-- Simple Header --}}
    <div class="mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Completed & Incomplete Trucks</h1>
            <p class="text-gray-600 text-sm mt-1">View all completed and incomplete disinfection slips</p>
        </div>
    </div>

    {{-- Search + Filter --}}
    <div class="mb-4 flex items-center gap-3">
        {{-- Search Bar with Filter Button Inside --}}
        <div class="relative flex-1">
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <input type="text" wire:model.live="search"
                class="block w-full pl-10 pr-24 py-2.5 bg-white border border-gray-300 rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                placeholder="Search...">
            <div class="absolute inset-y-0 right-0 flex items-center pr-1 gap-1">
                @if ($search)
                    <button wire:click="$set('search', '')"
                        class="flex items-center justify-center w-8 h-8 text-gray-400 hover:text-gray-600 rounded transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                @endif
                <button wire:click="$toggle('showFilters')" title="Filters" type="button"
                    class="inline-flex items-center justify-center w-8 h-8 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-blue-500 relative hover:cursor-pointer cursor-pointer
                        @if ($filtersActive) text-blue-600 bg-blue-50 hover:bg-blue-100
                        @else text-gray-500 hover:text-gray-700
                        @endif">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z">
                        </path>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Filter Modal --}}
    <x-modals.filter-modal>
        <x-slot name="filters">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Status Filter --}}
                <div x-data="{
                    open: false,
                    options: {'all': 'All', 'completed': 'Completed', 'incomplete': 'Incomplete'},
                    selected: @entangle('filterStatus').live,
                    placeholder: 'Select status',
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
                        <button type="button" wire:click="$set('filterStatus', 'all')"
                            x-show="selected !== 'all' && selected !== null && selected !== ''"
                            class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                            Clear
                        </button>
                    </div>

                    <div class="relative">
                        <button type="button" x-on:click="open = !open"
                            class="inline-flex justify-between w-full px-3 py-2 text-sm font-medium bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-blue-500"
                            :class="{ 'ring-2 ring-blue-500': open }">
                            <span :class="{ 'text-gray-400': selected === null || selected === undefined || selected === '' }">
                                <span x-text="displayText"></span>
                            </span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 ml-2 -mr-1 transition-transform"
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
                                    class="block px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-100 active:bg-blue-100 cursor-pointer rounded-md transition-colors"
                                    :class="{
                                        'bg-blue-50 text-blue-700': selected === value
                                    }">
                                    <span x-text="label"></span>
                                </a>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Plate Number Filter --}}
                <div x-data="{ filterValue: @entangle('filterPlateNumber') }">
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-medium text-gray-700">Plate Number</label>
                        <button type="button" wire:click="$set('filterPlateNumber', [])"
                            x-show="filterValue && filterValue.length > 0"
                            class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                            Clear
                        </button>
                    </div>
                    <x-forms.searchable-dropdown wireModel="filterPlateNumber" :options="$this->filterTruckOptions"
                        search-property="searchFilterPlateNumber" placeholder="Select plate no..."
                        search-placeholder="Search plate numbers..." :multiple="true" />
                </div>

                {{-- Driver Filter --}}
                <div x-data="{ filterValue: @entangle('filterDriver') }">
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-medium text-gray-700">Driver</label>
                        <button type="button" wire:click="$set('filterDriver', [])" x-show="filterValue && filterValue.length > 0"
                            class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                            Clear
                        </button>
                    </div>
                    <x-forms.searchable-dropdown wireModel="filterDriver" :options="$this->filterDriverOptions" search-property="searchFilterDriver"
                        placeholder="Select drivers..." search-placeholder="Search drivers..." :multiple="true" />
                </div>

                {{-- Destination Filter --}}
                <div x-data="{ filterValue: @entangle('filterDestination') }">
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-medium text-gray-700">Destination</label>
                        <button type="button" wire:click="$set('filterDestination', [])"
                            x-show="filterValue && filterValue.length > 0"
                            class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                            Clear
                        </button>
                    </div>
                    <x-forms.searchable-dropdown wireModel="filterDestination" :options="$this->filterDestinationOptions"
                        search-property="searchFilterDestination" placeholder="Select destination..."
                        search-placeholder="Search destinations..." :multiple="true" />
                </div>

                {{-- From Date Input --}}
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-medium text-gray-700">From Date</label>
                        <button type="button" wire:click="$set('filterCompletedFrom', '')"
                            x-show="$wire.filterCompletedFrom"
                            class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                            Clear
                        </button>
                    </div>
                    <input type="date" wire:model.live="filterCompletedFrom"
                        x-ref="fromDateInput"
                        @input="
                            const toInput = $refs.toDateInput;
                            const today = '<?php echo date('Y-m-d'); ?>';
                            if (toInput) {
                                // Set To Date min to From Date
                                toInput.min = $el.value || '';
                                // Clear To Date if it's before the new From Date
                                if (toInput.value && $el.value && toInput.value < $el.value) {
                                    toInput.value = '';
                                    $wire.set('filterCompletedTo', '');
                                }
                                // Validate From Date doesn't exceed To Date
                                if (toInput.value && $el.value && $el.value > toInput.value) {
                                    $el.value = '';
                                    $wire.set('filterCompletedFrom', '');
                                }
                            }
                        "
                        :max="$wire.filterCompletedTo || '<?php echo date('Y-m-d'); ?>'"
                        class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-blue-500">
                </div>

                {{-- To Date Input --}}
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-medium text-gray-700">To Date</label>
                        <button type="button" wire:click="$set('filterCompletedTo', '')"
                            x-show="$wire.filterCompletedTo"
                            class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                            Clear
                        </button>
                    </div>
                    <input type="date" wire:model.live="filterCompletedTo"
                        x-ref="toDateInput"
                        @input="
                            const fromInput = $refs.fromDateInput;
                            if (fromInput) {
                                // Clear From Date if it exceeds the new To Date
                                if (fromInput.value && $el.value && fromInput.value > $el.value) {
                                    fromInput.value = '';
                                    $wire.set('filterCompletedFrom', '');
                                }
                                // Clear To Date if it's before From Date
                                if (fromInput.value && $el.value && $el.value < fromInput.value) {
                                    $el.value = '';
                                    $wire.set('filterCompletedTo', '');
                                }
                            }
                        "
                        :min="$wire.filterCompletedFrom || ''"
                        max="<?php echo date('Y-m-d'); ?>"
                        class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-blue-500">
                </div>
            </div>

            {{-- Sort Section --}}
            <div class="mt-4 pt-4 border-t border-gray-200">
                <label class="block text-xs font-medium text-gray-700 mb-2">Sort by End Date</label>
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
        </x-slot>
    </x-modals.filter-modal>

    {{-- Disinfection Slip Details Modal --}}
    <livewire:trucks.disinfection-slip />

    {{-- Card List --}}
    <div @if (!$showFilters) wire:poll.keep-alive @endif class="space-y-3 pb-4">

        @forelse ($slips as $slip)
            @php
                $statusMap = [
                    0 => ['label' => 'Pending', 'color' => 'border-gray-500 bg-gray-50'],
                    1 => ['label' => 'Disinfecting', 'color' => 'border-orange-500 bg-orange-50'],
                    2 => ['label' => 'In-Transit', 'color' => 'border-yellow-500 bg-yellow-50'],
                    3 => ['label' => 'Completed', 'color' => 'border-green-500 bg-green-50'],
                    4 => ['label' => 'Incomplete', 'color' => 'border-red-500 bg-red-50'],
                ];
                $status = $slip->status;
            @endphp

            {{-- Card (Now Clickable) --}}
            <div wire:click="$dispatch('open-disinfection-details', { id: {{ $slip->id }}, type: 'incoming' })"
                class="flex justify-between items-center p-2.5 border-l-4 rounded-lg shadow-sm transition hover:shadow-md cursor-pointer {{ $statusMap[$status]['color'] }}">

                <div class="flex-1">
                    {{-- Slip No - Prominent --}}
                    <div class="mb-1">
                        <div class="text-[10px] font-medium text-gray-500 uppercase tracking-wide mb-0.5">Slip No</div>
                        <div class="text-base font-bold text-gray-900">{{ $slip->slip_id }}</div>
                    </div>

                    {{-- Date/Time --}}
                    @if ($slip->completed_at)
                    @php
                        $completedDate = \Carbon\Carbon::parse($slip->completed_at);
                        $isToday = $completedDate->isToday();
                    @endphp
                    <div class="flex items-center gap-1 text-xs">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="text-gray-600">
                            @if ($isToday)
                                {{ $completedDate->format('h:i A') }}
                            @else
                                {{ $completedDate->format('M d, Y') }}
                            @endif
                        </span>
                    </div>
                    @endif
                </div>

                {{-- Right Side --}}
                <div class="flex flex-col items-end justify-center ml-3">
                    {{-- Status Badge --}}
                    <span
                        class="px-2 py-0.5 text-[10px] font-semibold rounded-full whitespace-nowrap
                        {{ $status === 0 ? 'bg-gray-100 text-gray-700' : '' }}
                        {{ $status === 1 ? 'bg-orange-100 text-orange-700' : '' }}
                        {{ $status === 2 ? 'bg-yellow-100 text-yellow-700' : '' }}
                        {{ $status === 3 ? 'bg-green-100 text-green-700' : '' }}
                        {{ $status === 4 ? 'bg-red-100 text-red-700' : '' }}">
                        {{ $statusMap[$status]['label'] }}
                    </span>
                </div>
            </div>

        @empty

            <div class="text-center py-6 text-gray-500">
                No truck slips found.
            </div>
        @endforelse

    </div>

    {{-- Pagination --}}
    <x-buttons.nav-pagination :paginator="$slips" />
</div>
