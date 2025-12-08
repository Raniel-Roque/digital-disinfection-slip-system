<div class="max-w-full bg-white border border-gray-200 rounded-xl shadow-sm p-4 m-4"
    @if (!$showFilters && !$showDetailsModal) wire:poll.keep-alive @endif>

    {{-- Search + Filter + Sort --}}
    <div class="mb-4 flex items-center gap-3">
        {{-- Search Bar --}}
        <div class="relative w-full">
            <label class="sr-only">Search</label>
            <input type="text" wire:model.live="search"
                class="py-2 px-3 ps-9 block w-full border-gray-200 shadow-sm rounded-lg sm:text-sm 
                        focus:border-blue-500 focus:ring-blue-500"
                placeholder="Search by description or slip no...">
            <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none ps-3">
                <svg class="size-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.3-4.3"></path>
                </svg>
            </div>
        </div>

        {{-- Filter Button (Icon Only) --}}
        <button wire:click="$toggle('showFilters')" type="button"
            class="p-2.5 bg-orange-500 hover:bg-orange-600 text-white rounded-lg transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18M6 12h12m-7 8h2" />
            </svg>
        </button>

        {{-- Sort Button (Icon Only) --}}
        <button wire:click="toggleSort" type="button"
            class="p-2.5 text-white rounded-lg transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2
                @if ($sortDirection === 'asc') bg-green-500 hover:bg-green-600 focus:ring-green-500
                @elseif ($sortDirection === 'desc') bg-red-500 hover:bg-red-600 focus:ring-red-500
                @else bg-gray-500 hover:bg-gray-600 focus:ring-gray-500 @endif">
            <div class="flex flex-col items-center">
                @if ($sortDirection === 'asc')
                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                    </svg>
                    <svg class="w-3 h-3 text-white opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                @elseif ($sortDirection === 'desc')
                    <svg class="w-3 h-3 text-white opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                    </svg>
                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                @else
                    <svg class="w-3 h-3 text-white opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                    </svg>
                    <svg class="w-3 h-3 text-white opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                @endif
            </div>
        </button>
    </div>

    {{-- Filter Modal --}}
    <x-modals.filter-modal>
        <x-slot name="filters">
            <x-modals.filter-reports-body :availableStatuses="$availableStatuses" />
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
                class="flex justify-between items-center p-4 border-l-4 rounded-lg shadow-sm transition hover:shadow-md cursor-pointer {{ $statusColor }}">

                <div class="grid grid-cols-2 gap-y-2 text-sm">
                    <div class="font-semibold text-gray-600">Date:</div>
                    <div class="text-gray-800">
                        {{ $report->created_at->format('M d, Y') }}
                        <span class="text-gray-500 text-xs ml-1">
                            {{ $report->created_at->format('h:i A') }}
                        </span>
                    </div>

                    @if ($report->slip_id)
                        <div class="font-semibold text-gray-600">Slip No:</div>
                        <div class="text-gray-800 font-medium">{{ $report->slip->slip_id ?? 'N/A' }}</div>
                    @else
                        <div class="font-semibold text-gray-600">Type:</div>
                        <div class="text-gray-800 italic">Miscellaneous</div>
                    @endif
                </div>

                {{-- Right Side: Status Badge --}}
                <div class="flex flex-col items-end">
                    @if ($report->resolved_at)
                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700">
                            Resolved
                        </span>
                    @else
                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700">
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
    <x-modals.modal-template show="showDetailsModal" title="REPORT DETAILS" max-width="max-w-2xl">
        @if ($selectedReport)
            <div class="py-4 space-y-4">
                {{-- Date Created --}}
                <div class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1">
                    <div class="font-semibold text-gray-700 text-sm">Date Created:</div>
                    <div class="text-gray-900 text-sm">
                        {{ $selectedReport->created_at->format('M d, Y') }}
                        <span class="text-gray-500 ml-1">
                            {{ $selectedReport->created_at->format('h:i A') }}
                        </span>
                    </div>
                </div>

                {{-- Completion Date (if resolved) --}}
                @if ($selectedReport->resolved_at)
                    <div class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1">
                        <div class="font-semibold text-gray-700 text-sm">Completion Date:</div>
                        <div class="text-gray-900 text-sm">
                            {{ $selectedReport->resolved_at->format('M d, Y') }}
                            <span class="text-gray-500 ml-1">
                                {{ $selectedReport->resolved_at->format('h:i A') }}
                            </span>
                        </div>
                    </div>
                @endif

                {{-- Slip No or Type --}}
                <div class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1">
                    <div class="font-semibold text-gray-700 text-sm">
                        @if ($selectedReport->slip_id)
                            Slip No:
                        @else
                            Type:
                        @endif
                    </div>
                    <div class="text-gray-900 text-sm">
                        @if ($selectedReport->slip_id)
                            <span class="font-medium">{{ $selectedReport->slip->slip_id ?? 'N/A' }}</span>
                        @else
                            <span class="italic">Miscellaneous</span>
                        @endif
                    </div>
                </div>

                {{-- Status --}}
                <div class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1">
                    <div class="font-semibold text-gray-700 text-sm">Status:</div>
                    <div>
                        @if ($selectedReport->resolved_at)
                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700">
                                Resolved
                            </span>
                        @else
                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700">
                                Unresolved
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Divider --}}
                <div class="border-t border-gray-200"></div>

                {{-- Description --}}
                <div>
                    <div class="font-semibold text-gray-700 text-sm mb-2">Description:</div>
                    <div
                        class="p-4 bg-gray-50 rounded-lg border border-gray-200 text-sm text-gray-700 whitespace-pre-wrap">
                        {{ $selectedReport->description }}
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
