<div class="min-h-screen bg-gray-50 p-6">
    <div class="max-w-7xl mx-auto">
        {{-- Header --}}
        <div class="mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Reports</h1>
                    <p class="text-gray-600 text-sm mt-1">View and manage all reports</p>
                </div>

                {{-- Search and Filter Bar --}}
                <div class="flex gap-3 w-full lg:w-auto">
                    {{-- Search Bar with Filter Button Inside --}}
                    <div class="relative flex-1 lg:w-96">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" wire:model.live="search"
                            class="block w-full pl-10 {{ $search ? 'pr-20' : 'pr-12' }} py-2.5 bg-white border border-gray-300 rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                            placeholder="Search by name, slip no...">
                        
                        {{-- Right Side Buttons Container --}}
                        <div class="absolute inset-y-0 right-0 flex items-center pr-2 gap-1">
                            {{-- Clear Button (X) - Only when search has text --}}
                        @if ($search)
                            <button wire:click="$set('search', '')"
                                    class="flex items-center text-gray-400 hover:text-gray-600 transition-colors duration-150 hover:cursor-pointer cursor-pointer">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        @endif

                            {{-- Filter Button Inside Search (Right Side) --}}
                    <button wire:click="$toggle('showFilters')" title="Filters"
                                class="flex items-center text-gray-400 hover:text-gray-600 transition-colors duration-150 focus:outline-none hover:cursor-pointer cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z">
                            </path>
                        </svg>
                    </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Active Filters Display --}}
            @if ($filtersActive)
                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="text-sm text-gray-600">Active filters:</span>

                    @if (!is_null($appliedResolved))
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            Status: {{ $appliedResolved == 1 ? 'Resolved' : 'Unresolved' }}
                            <button wire:click="removeFilter('resolved')"
                                class="ml-1.5 inline-flex items-center hover:cursor-pointer">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                        clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </span>
                    @endif

                    @if (!empty($appliedCreatedFrom))
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            From: {{ \Carbon\Carbon::parse($appliedCreatedFrom)->format('M d, Y') }}
                            <button wire:click="removeFilter('created_from')"
                                class="ml-1.5 inline-flex items-center hover:cursor-pointer">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                        clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </span>
                    @endif

                    @if (!empty($appliedCreatedTo))
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            To: {{ \Carbon\Carbon::parse($appliedCreatedTo)->format('M d, Y') }}
                            <button wire:click="removeFilter('created_to')"
                                class="ml-1.5 inline-flex items-center hover:cursor-pointer">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                        clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </span>
                    @endif

                    <button wire:click="clearFilters"
                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-100 transition-colors hover:cursor-pointer cursor-pointer">
                        Clear all
                    </button>
                </div>
            @endif
        </div>

        {{-- Table Card --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <div class="inline-flex items-center gap-2">
                                    <span>Date & Time</span>
                                    <button wire:click.prevent="applySort('created_at')" type="button"
                                        class="inline-flex flex-col items-center text-gray-500 hover:text-gray-700 focus:outline-none focus:text-gray-700 transition-colors p-0.5 rounded hover:bg-gray-200 hover:cursor-pointer cursor-pointer"
                                        title="Sort by Date">
                                        @if ($sortBy === 'created_at')
                                            @if ($sortDirection === 'asc')
                                                <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M5 15l7-7 7 7" />
                                                </svg>
                                                <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            @else
                                                <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M5 15l7-7 7 7" />
                                                </svg>
                                                <svg class="w-3 h-3 text-red-600" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            @endif
                                        @else
                                            <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 15l7-7 7 7" />
                                            </svg>
                                            <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        @endif
                                    </button>
                                </div>
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Name
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Type
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($reports as $report)
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900">
                                        {{ $report->created_at->format('M d, Y') }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-0.5">
                                        {{ $report->created_at->format('h:i A') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ trim($report->user->first_name . ' ' . ($report->user->middle_name ?? '') . ' ' . $report->user->last_name) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900">
                                        @if ($report->slip_id && $report->slip)
                                            <button wire:click="$dispatch('open-disinfection-details', { id: {{ $report->slip->id }}, type: 'incoming' })" 
                                                class="text-blue-600 hover:text-blue-800 hover:underline transition-colors duration-150 hover:cursor-pointer cursor-pointer">
                                                Slip: {{ $report->slip->slip_id ?? 'N/A' }}
                                            </button>
                                        @elseif ($report->slip_id)
                                            <span class="text-blue-600">Slip: {{ $report->slip_id ?? 'N/A' }}</span>
                                        @else
                                            <span class="text-gray-500 italic">Miscellaneous</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($report->resolved_at)
                                        <div class="flex flex-col">
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 w-fit">
                                                Resolved
                                            </span>
                                            <div class="text-xs text-gray-500 mt-1">
                                                {{ $report->resolved_at->format('M d, Y') }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ $report->resolved_at->format('h:i A') }}
                                            </div>
                                        </div>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Unresolved
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <div class="flex items-center justify-center gap-2">
                                        <button wire:click="openDetailsModal({{ $report->id }})"
                                            class="hover:cursor-pointer inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            View Details
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                            </path>
                                        </svg>
                                        <p class="text-gray-500 text-sm">No reports found</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination Footer --}}
            <div class="px-6 bg-gray-50 border-t border-gray-200">
                <x-buttons.nav-pagination :paginator="$reports" />
            </div>
        </div>

        {{-- Filter Modal --}}
        <x-modals.filter-modal>
            <x-slot name="filters">
                <x-modals.filter-reports-body :availableStatuses="$availableStatuses" />
            </x-slot>
        </x-modals.filter-modal>

        {{-- View Details Modal --}}
        @if ($showDetailsModal && $selectedReport)
            @php
                $isResolved = $selectedReport->resolved_at !== null;
                $headerClass = $isResolved ? 'border-t-4 border-t-green-500 bg-green-50' : 'border-t-4 border-t-red-500 bg-red-50';
            @endphp
            <x-modals.modal-template show="showDetailsModal" title="REPORT DETAILS" max-width="max-w-3xl" header-class="{{ $headerClass }}">
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
                        {{-- Name --}}
                        <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-white">
                            <div class="font-semibold text-gray-500">Name:</div>
                            <div class="text-gray-900">
                                {{ trim($selectedReport->user->first_name . ' ' . ($selectedReport->user->middle_name ?? '') . ' ' . $selectedReport->user->last_name) }}
                            </div>
                        </div>

                        {{-- Type --}}
                        <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-gray-100">
                            <div class="font-semibold text-gray-500">Type:</div>
                            <div class="text-gray-900">
                                @if ($selectedReport->slip_id)
                                    <span class="text-blue-600 font-semibold">Slip: {{ $selectedReport->slip->slip_id ?? 'N/A' }}</span>
                                @else
                                    <span class="text-gray-500 italic">Miscellaneous</span>
                                @endif
                            </div>
                        </div>

                        {{-- Description --}}
                        <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-white">
                            <div class="font-semibold text-gray-500">Description:</div>
                            <div class="text-gray-900 wrap-break-words min-w-0" style="word-break: break-word; overflow-wrap: break-word;">
                                <div class="whitespace-pre-wrap">{{ $selectedReport->description ?? 'No description provided.' }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Sub Footer --}}
                    @if ($selectedReport->resolved_at)
                        <div class="border-t border-gray-200 px-6 py-2 bg-gray-50 -mx-6 -mb-6 mt-2">
                            <div class="grid grid-cols-2 gap-4 text-xs">
                                <div>
                                    <div class="font-bold text-gray-500 mb-0.5">Resolved by:</div>
                                    <div class="text-gray-900">
                                        {{ $selectedReport->resolvedBy ? trim($selectedReport->resolvedBy->first_name . ' ' . ($selectedReport->resolvedBy->middle_name ?? '') . ' ' . $selectedReport->resolvedBy->last_name) : 'N/A' }}
                                    </div>
                                </div>
                                <div>
                                    <div class="font-bold text-gray-500 mb-0.5">Resolved on:</div>
                                    <div class="text-gray-900">
                                        {{ $selectedReport->resolved_at->format('M d, Y - h:i A') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @else
                    <p class="text-gray-500 text-center">No details available.</p>
                @endif

                {{-- Footer --}}
                <x-slot name="footer">
                    <div class="flex justify-end w-full gap-2">
                        <x-buttons.submit-button wire:click="closeDetailsModal" color="white">
                            Close
                        </x-buttons.submit-button>

                        @if (!$selectedReport->resolved_at)
                            <x-buttons.submit-button wire:click.prevent="resolveReport" color="green" wire:loading.attr="disabled" wire:target="resolveReport"
                                x-bind:disabled="$wire.isResolving">
                                <span wire:loading.remove wire:target="resolveReport">Resolve</span>
                                <span wire:loading wire:target="resolveReport">Resolving...</span>
                            </x-buttons.submit-button>
                        @else
                            <x-buttons.submit-button wire:click.prevent="unresolveReport" color="orange" wire:loading.attr="disabled" wire:target="unresolveReport"
                                x-bind:disabled="$wire.isResolving">
                                <span wire:loading.remove wire:target="unresolveReport">Unresolve</span>
                                <span wire:loading wire:target="unresolveReport">Unresolving...</span>
                            </x-buttons.submit-button>
                        @endif
                    </div>
                </x-slot>
            </x-modals.modal-template>
        @endif
    </div>
</div>
