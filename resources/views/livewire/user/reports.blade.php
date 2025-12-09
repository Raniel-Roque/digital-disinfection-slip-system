<div class="max-w-full bg-white border border-gray-200 rounded-xl shadow-sm p-4 m-4"
    @if (!$showFilters && !$showDetailsModal) wire:poll.keep-alive @endif>

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
                placeholder="Search by description or slip no...">
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
            <x-modals.filter-reports-body :availableStatuses="$availableStatuses" :filterSortDirection="$filterSortDirection" />
        </x-slot>
    </x-modals.filter-modal>


    {{-- Card List --}}
    <div class="space-y-3 pb-4">
        @forelse($reports as $report)
            @php
                $statusColor = $report->resolved_at ? 'border-green-500 bg-green-50' : 'border-red-500 bg-red-50';
            @endphp

            {{-- Card (Clickable) --}}
            <div wire:click="openDetailsModal({{ $report->id }})"
                class="flex justify-between items-center p-2.5 border-l-4 rounded-lg shadow-sm transition hover:shadow-md cursor-pointer {{ $statusColor }}">

                <div class="flex-1">
                    {{-- Slip No/Type - Prominent --}}
                    <div class="mb-1">
                        <div class="text-[10px] font-medium text-gray-500 uppercase tracking-wide mb-0.5">
                            @if ($report->slip_id)
                                Slip No
                            @else
                                Type
                            @endif
                        </div>
                        <div class="text-base font-bold text-gray-900">
                            @if ($report->slip_id)
                                {{ $report->slip->slip_id ?? 'N/A' }}
                            @else
                                <span class="italic font-normal">Miscellaneous</span>
                            @endif
                        </div>
                    </div>

                    {{-- Date/Time --}}
                    @php
                        $createdDate = \Carbon\Carbon::parse($report->created_at);
                        $isToday = $createdDate->isToday();
                    @endphp
                    <div class="flex items-center gap-1 text-xs">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="text-gray-600">
                            @if ($isToday)
                                {{ $createdDate->format('h:i A') }}
                            @else
                                {{ $createdDate->format('M d, Y') }}
                            @endif
                        </span>
                    </div>
                </div>

                {{-- Right Side: Status Badge --}}
                <div class="flex flex-col items-end justify-center ml-3">
                    @if ($report->resolved_at)
                        <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full bg-green-100 text-green-700 whitespace-nowrap">
                            Resolved
                        </span>
                    @else
                        <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full bg-red-100 text-red-700 whitespace-nowrap">
                            Unresolved
                        </span>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center py-12 text-gray-500">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                @if ($search || !is_null($appliedResolved) || !empty($appliedCreatedFrom) || !empty($appliedCreatedTo))
                    <p class="text-lg font-medium">No results match your filters.</p>
                @else
                    <p class="text-lg font-medium">You haven't submitted any reports yet.</p>
                @endif
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <x-buttons.nav-pagination :paginator="$reports" />

    {{-- Report Details Modal --}}
    @php
        $isResolved = $selectedReport?->resolved_at !== null;
        $headerClass = $isResolved ? 'border-t-4 border-t-green-500 bg-green-50' : 'border-t-4 border-t-red-500 bg-red-50';
    @endphp
    <x-modals.modal-template show="showDetailsModal" title="REPORT DETAILS" max-width="max-w-2xl" header-class="{{ $headerClass }}">
        @if ($selectedReport)
            {{-- Sub Header --}}
            <div class="border-b border-gray-200 px-6 py-2 bg-gray-50 -mx-6 -mt-6 mb-2">
                <div class="grid grid-cols-[1fr_1fr] gap-4 items-start text-xs">
                    <div>
                        <div class="font-semibold text-gray-500 mb-0.5">Date:</div>
                        <div class="text-gray-900">{{ $selectedReport->created_at->format('M d, Y') }}</div>
                    </div>
                    <div>
                        <div class="font-semibold text-gray-500 mb-0.5">
                            @if ($selectedReport->slip_id)
                                Slip No:
                            @else
                                Type:
                            @endif
                        </div>
                        <div class="text-gray-900 font-semibold">
                            @if ($selectedReport->slip_id)
                                {{ $selectedReport->slip->slip_id ?? 'N/A' }}
                            @else
                                <span class="italic font-normal">Miscellaneous</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Body Fields --}}
            <div class="space-y-0 -mx-6">
                {{-- Description --}}
                <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-white">
                    <div class="font-semibold text-gray-500">Description:</div>
                    <div class="text-gray-900 wrap-break-words min-w-0" style="word-break: break-word; overflow-wrap: break-word;">
                        <div class="whitespace-pre-wrap">{{ $selectedReport->description }}</div>
                    </div>
                </div>
            </div>
        @endif

        <x-slot name="footer">
            <x-buttons.submit-button wire:click="closeDetailsModal" color="white">
                Close
            </x-buttons.submit-button>
        </x-slot>
    </x-modals.modal-template>
</div>
