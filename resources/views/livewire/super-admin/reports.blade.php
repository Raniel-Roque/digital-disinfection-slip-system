<div class="min-h-screen bg-gray-50 p-6" @if (!$showFilters && !$showDetailsModal && !$showRestoreModal) wire:poll.keep-alive @endif>
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

                    {{-- Restore Deleted Button --}}
                    <button wire:click="toggleDeletedView" wire:loading.attr="disabled" wire:target="toggleDeletedView"
                        class="inline-flex items-center px-4 py-2.5 {{ $showDeleted ? 'bg-gray-600 hover:bg-gray-700' : 'bg-orange-600 hover:bg-orange-700' }} text-white rounded-lg text-sm font-medium transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 {{ $showDeleted ? 'focus:ring-gray-500' : 'focus:ring-orange-500' }} disabled:opacity-50 disabled:cursor-not-allowed hover:cursor-pointer cursor-pointer">
                        <svg wire:loading.remove wire:target="toggleDeletedView" class="w-5 h-5 mr-2" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                            </path>
                        </svg>

                        <span wire:loading.remove
                            wire:target="toggleDeletedView">{{ $showDeleted ? 'Back to Active' : 'Restore Deleted' }}</span>
                        <span wire:loading.inline-flex wire:target="toggleDeletedView" class="inline-flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Loading...
                        </span>
                    </button>
                </div>
            </div>

            {{-- Active Filters Display --}}
            @if ($filtersActive && !$showDeleted)
                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="text-sm text-gray-600">Active filters:</span>

                    @if ($excludeDeletedItems)
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            Excluding reports with deleted items
                            <button wire:click="$set('excludeDeletedItems', false)" class="ml-1.5 inline-flex items-center hover:cursor-pointer cursor-pointer">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                        clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </span>
                    @endif

                    @if (!is_null($appliedResolved) && $appliedResolved !== '')
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            Status: {{ $appliedResolved == '1' || $appliedResolved === 1 ? 'Resolved' : 'Unresolved' }}
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

                    @if (!is_null($appliedReportType))
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            Type: {{ $appliedReportType === 'slip' ? 'Slip' : 'Miscellaneous' }}
                            <button wire:click="removeFilter('report_type')"
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
            @elseif (($appliedCreatedFrom || $appliedCreatedTo) && $showDeleted)
                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="text-sm text-gray-600">Active filters (Restore Mode):</span>

                    @if ($appliedCreatedFrom)
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            From: {{ \Carbon\Carbon::parse($appliedCreatedFrom)->format('M j, Y') }}
                            <button wire:click="removeFilter('created_from')" class="ml-1.5 inline-flex items-center hover:cursor-pointer">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                        clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </span>
                    @endif

                    @if ($appliedCreatedTo)
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            To: {{ \Carbon\Carbon::parse($appliedCreatedTo)->format('M j, Y') }}
                            <button wire:click="removeFilter('created_to')" class="ml-1.5 inline-flex items-center hover:cursor-pointer">
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
                                Report ID
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <div class="inline-flex items-center gap-2">
                                    <span>Date & Time</span>
                                    <button wire:click.prevent="applySort('created_at')" type="button"
                                        class="inline-flex flex-col items-center text-gray-500 hover:text-gray-700 focus:outline-none focus:text-gray-700 transition-colors p-0.5 rounded hover:bg-gray-200 hover:cursor-pointer cursor-pointer"
                                        title="Sort by Date">
                                        @if ($sortBy === 'created_at')
                                            @if ($sortDirection === 'asc')
                                                <svg class="w-3 h-3 text-green-600" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M5 15l7-7 7 7" />
                                                </svg>
                                                <svg class="w-3 h-3 text-gray-300" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            @else
                                                <svg class="w-3 h-3 text-gray-300" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M5 15l7-7 7 7" />
                                                </svg>
                                                <svg class="w-3 h-3 text-red-600" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
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
                                        {{ $report->id }}
                                    </div>
                                </td>
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
                                        @if ($report->user && !(method_exists($report->user, 'trashed') && $report->user->trashed()))
                                            {{ trim($report->user->first_name . ' ' . ($report->user->middle_name ?? '') . ' ' . $report->user->last_name) }}
                                        @elseif ($report->user)
                                            {{ trim($report->user->first_name . ' ' . ($report->user->middle_name ?? '') . ' ' . $report->user->last_name) }}
                                            <span class="text-red-600 font-semibold"> (Deleted)</span>
                                        @else
                                            <span class="text-gray-500 italic">User Deleted</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-500 mt-0.5">
                                        @if ($report->user && !(method_exists($report->user, 'trashed') && $report->user->trashed()))
                                            &#64;{{ $report->user->username }}
                                        @elseif ($report->user)
                                            &#64;{{ $report->user->username }}
                                        @else
                                            <span class="text-gray-500 italic">@user-deleted</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900">
                                        @if ($report->slip_id)
                                            @if ($report->slip && !(method_exists($report->slip, 'trashed') && $report->slip->trashed()))
                                                <button wire:click="openSlipDetailsModal({{ $report->slip->id }})"
                                                    class="text-blue-600 hover:text-blue-800 hover:underline transition-colors duration-150 hover:cursor-pointer cursor-pointer">
                                                    Slip: {{ $report->slip->slip_id ?? 'N/A' }}
                                                </button>
                                            @elseif ($report->slip)
                                                <span class="text-gray-900 font-semibold">Slip: {{ $report->slip->slip_id ?? $report->slip_id }} <span class="text-gray-500 italic">(deleted)</span></span>
                                            @else
                                                <span class="text-gray-900 font-semibold">Slip: {{ $report->slip_id }} <span class="text-gray-500 italic">(deleted)</span></span>
                                            @endif
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
                                                {{ $report->resolved_at->format('M d, Y h:i A') }}
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
                                    @if ($showDeleted)
                                        <x-buttons.submit-button wire:click="openRestoreModal({{ $report->id }})"
                                            color="green" size="sm" :fullWidth="false">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                                </path>
                                            </svg>
                                            <span>Restore</span>
                                        </x-buttons.submit-button>
                                    @else
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

                                            <button wire:click="openDeleteConfirmation({{ $report->id }})"
                                                class="hover:cursor-pointer inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                Delete
                                            </button>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
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
        @if ($showDeleted)
            {{-- Restore Mode Filter Modal - Only Date Filters --}}
            <x-modals.filter-modal>
                <x-slot name="filters">
                    <x-filter-restore-body />
                </x-slot>
            </x-modals.filter-modal>
        @else
            {{-- Normal Filter Modal - All Filters --}}
            <x-modals.filter-modal>
                <x-slot name="filters">
                    <x-modals.filter-reports-body :availableStatuses="$availableStatuses" />
                </x-slot>
            </x-modals.filter-modal>
        @endif

        {{-- Delete/Restore actions removed to make Reports view-only --}}

        {{-- Restore Confirmation Modal --}}
        @if ($showRestoreModal)
            <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
                aria-modal="true">
                {{-- Backdrop --}}
                <div class="fixed inset-0 transition-opacity bg-black/80" wire:click="closeRestoreModal"></div>

                {{-- Modal Panel --}}
                <div class="flex min-h-full items-center justify-center p-4">
                    <div
                        class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all w-full max-w-lg">
                        <div class="px-6 py-4 bg-white border-b border-gray-200">
                            <div class="flex items-center">
                                <div class="flex items-center justify-center w-12 h-12 bg-orange-100 rounded-full">
                                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                        </path>
                                    </svg>
                                </div>
                                <h3 class="ml-4 text-lg font-semibold text-gray-900">Restore Report</h3>
                            </div>
                        </div>

                        <div class="px-6 py-4">
                    @csrf
                            <p class="text-sm text-gray-600">
                                Are you sure you want to restore this report <span
                                    class="font-medium text-gray-900">{{ $selectedReportName }}</span>?
                                The report will be available again.
                            </p>
                        </div>

                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                            <button wire:click="closeRestoreModal" wire:loading.attr="disabled" wire:target="restoreReport"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 cursor-pointer hover:cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                                Cancel
                            </button>
                            <button wire:click.prevent="restoreReport" wire:loading.attr="disabled" wire:target="restoreReport"
                                x-bind:disabled="$wire.isRestoring"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed hover:cursor-pointer cursor-pointer">
                                <span wire:loading.remove wire:target="restoreReport">Restore Report</span>
                                <span wire:loading.inline-flex wire:target="restoreReport" class="inline-flex items-center gap-2"><svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Restoring...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- View Details Modal --}}
        @if ($showDetailsModal && $selectedReport && !$selectedSlip)
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
                                @if ($selectedReport->user)
                                    {{ trim($selectedReport->user->first_name . ' ' . ($selectedReport->user->middle_name ?? '') . ' ' . $selectedReport->user->last_name) }}
                                    @if ($selectedReport->user->trashed())
                                        <span class="text-red-600 font-semibold"> (Deleted)</span>
                                    @endif
                                    <div class="text-xs text-gray-500 mt-0.5">
                                        &#64;{{ $selectedReport->user->username }}
                                    </div>
                                @else
                                    <span class="text-gray-500 italic">User Deleted</span>
                                    <div class="text-xs text-gray-500 mt-0.5"><span class="text-gray-500 italic">@user-deleted</span></div>
                                @endif
                            </div>
                        </div>

                        {{-- Type --}}
                        <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-gray-100">
                            <div class="font-semibold text-gray-500">Type:</div>
                            <div class="text-gray-900">
                                @if ($selectedReport->slip_id)
                                    @if ($selectedReport->slip && !(method_exists($selectedReport->slip, 'trashed') && $selectedReport->slip->trashed()))
                                        <span class="text-blue-600 font-semibold">Slip: {{ $selectedReport->slip->slip_id ?? 'N/A' }}</span>
                                    @elseif ($selectedReport->slip)
                                        <span class="text-gray-900 font-semibold">Slip: {{ $selectedReport->slip->slip_id ?? $selectedReport->slip_id }} <span class="text-gray-500 italic">(deleted)</span></span>
                                    @else
                                        <span class="text-gray-900 font-semibold">Slip: {{ $selectedReport->slip_id }} <span class="text-gray-500 italic">(deleted)</span></span>
                                    @endif
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
                                    <div>
                                        @if ($selectedReport->resolvedBy)
                                            <span class="text-gray-900">{{ trim($selectedReport->resolvedBy->first_name . ' ' . ($selectedReport->resolvedBy->middle_name ?? '') . ' ' . $selectedReport->resolvedBy->last_name) }}</span>
                                            @if ($selectedReport->resolvedBy->trashed())
                                                <span class="text-red-600 font-semibold">(Deleted)</span>
                                            @endif
                                        @else
                                            <span class="text-gray-900">N/A</span>
                                        @endif
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
                                <span wire:loading.inline-flex wire:target="resolveReport" class="inline-flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Resolving...
                                </span>
                            </x-buttons.submit-button>
                        @else
                            <x-buttons.submit-button wire:click.prevent="unresolveReport" color="orange" wire:loading.attr="disabled" wire:target="unresolveReport"
                                x-bind:disabled="$wire.isResolving">
                                <span wire:loading.remove wire:target="unresolveReport">Unresolve</span>
                                <span wire:loading.inline-flex wire:target="unresolveReport" class="inline-flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Unresolving...
                                </span>
                            </x-buttons.submit-button>
                        @endif
                    </div>
                </x-slot>
            </x-modals.modal-template>
        @endif

        {{-- Slip Details Modal --}}
        @include('livewire.admin.slip-details-modal')

        {{-- Admin Edit Modal --}}
        @if ($selectedSlip && $showEditModal)
            <x-modals.admin-slip-edit-modal :trucks="$trucks" :locations="$locations" :drivers="$drivers" :guards="$guards"
                :available-origins-options="$editAvailableOriginsOptions" :available-destinations-options="$editAvailableDestinationsOptions" :edit-truck-options="$editTruckOptions" :edit-driver-options="$editDriverOptions" :edit-guard-options="$editGuardOptions"
                :edit-received-guard-options="$editReceivedGuardOptions" :slip-status="$selectedSlip->status" :edit-status="$editStatus" :selected-slip="$selectedSlip" />
        @endif

        {{-- Slip Delete Confirmation Modal --}}
        @if ($selectedSlip)
            <x-modals.delete-confirmation show="showSlipDeleteConfirmation" title="DELETE SLIP?"
                message="Delete this disinfection slip?" :details="'Slip No: <span class=\'font-semibold\'>' . ($selectedSlip?->slip_id ?? '') . '</span>'" warning="This action cannot be undone!"
                onConfirm="deleteSlip" />
        @endif

        {{-- Report Delete Confirmation Modal --}}
        @if ($showDeleteConfirmation && $selectedReportId)
            <x-modals.delete-confirmation show="showDeleteConfirmation" title="DELETE REPORT?"
                message="Delete this report?" :details="'Report ID: <span class=\'font-semibold\'>' . $selectedReportId . '</span>'" warning="This action cannot be undone!"
                onConfirm="deleteReport" />
        @endif
    </div>
</div>
