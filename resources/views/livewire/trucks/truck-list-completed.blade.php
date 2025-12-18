<div class="max-w-full bg-white border border-gray-200 rounded-xl shadow-sm p-4 m-4">

    {{-- Simple Header --}}
    <div class="mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Completed Trucks</h1>
            <p class="text-gray-600 text-sm mt-1">View all completed disinfection slips</p>
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
                <div x-data="{ toDate: @entangle('filterCompletedTo'), fromDate: @entangle('filterCompletedFrom') }">
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-medium text-gray-700">From Date</label>
                        <button type="button" wire:click="$set('filterCompletedFrom', null)" x-show="fromDate"
                            class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                            Clear
                        </button>
                    </div>
                    <input type="date" wire:model="filterCompletedFrom" :max="toDate || '{{ date('Y-m-d') }}'"
                        class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-blue-500">
                </div>

                {{-- To Date Input --}}
                <div x-data="{ fromDate: @entangle('filterCompletedFrom'), toDate: @entangle('filterCompletedTo') }">
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-medium text-gray-700">To Date</label>
                        <button type="button" wire:click="$set('filterCompletedTo', null)" x-show="toDate"
                            class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                            Clear
                        </button>
                    </div>
                    <input type="date" wire:model="filterCompletedTo" :min="fromDate" max="{{ date('Y-m-d') }}"
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
                    0 => ['label' => 'Ongoing', 'color' => 'border-red-500 bg-red-50'],
                    1 => ['label' => 'Disinfecting', 'color' => 'border-orange-500 bg-orange-50'],
                    2 => ['label' => 'Completed', 'color' => 'border-green-500 bg-green-50'],
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
                        {{ $status === 0 ? 'bg-red-100 text-red-700' : '' }}
                        {{ $status === 1 ? 'bg-orange-100 text-orange-700' : '' }}
                        {{ $status === 2 ? 'bg-green-100 text-green-700' : '' }}">
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
