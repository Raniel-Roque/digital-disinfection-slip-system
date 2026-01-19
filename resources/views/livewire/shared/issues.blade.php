<div class="min-h-screen bg-gray-50 p-6" @if (!$showFilters && !$showDetailsModal) wire:poll.keep-alive @endif>
    <div class="max-w-7xl mx-auto">
        {{-- Header --}}
        <div class="mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Issues</h1>
                    <p class="text-gray-600 text-sm mt-1">View and manage all issues</p>
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

                    {{-- Restore Button - Only for super-admin --}}
                    @if ($showRestore)
                    <button wire:click="toggleDeletedView" wire:loading.attr="disabled" wire:target="toggleDeletedView"
                        class="inline-flex items-center px-4 py-2.5 {{ $showDeleted ? 'bg-gray-600 hover:bg-gray-700' : 'bg-orange-600 hover:bg-orange-700' }} text-white rounded-lg text-sm font-medium transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 {{ $showDeleted ? 'focus:ring-gray-500' : 'focus:ring-orange-500' }} disabled:opacity-50 disabled:cursor-not-allowed hover:cursor-pointer cursor-pointer">
                        <svg wire:loading.remove wire:target="toggleDeletedView" class="w-5 h-5 mr-2" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                            </path>
                        </svg>

                        <span wire:loading.remove
                            wire:target="toggleDeletedView">{{ $showDeleted ? 'Back to Active' : 'Restore' }}</span>
                        <span wire:loading.inline-flex wire:target="toggleDeletedView" class="inline-flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Loading...
                        </span>
                    </button>
                    @endif
                </div>
            </div>

            {{-- Active Filters Display --}}
            @if (($filtersActive || ($showRestore && $excludeDeletedItems)) && !$showDeleted)
                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="text-sm text-gray-600">Active filters:</span>

                    @if ($showRestore && $excludeDeletedItems)
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            Excluding issues with deleted items
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

                    @if (!is_null($appliedIssueType))
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            Type: {{ $appliedIssueType === 'slip' ? 'Slip' : 'Miscellaneous' }}
                            <button wire:click="removeFilter('issue_type')"
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
            @elseif ($showRestore && ($appliedCreatedFrom || $appliedCreatedTo) && $showDeleted)
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
                                Issue ID
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
                        @forelse($issues as $issue)
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900">
                                        {{ $issue->id }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900">
                                        {{ $issue->created_at->format('M d, Y') }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-0.5">
                                        {{ $issue->created_at->format('h:i A') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        @if ($issue->user && !(method_exists($issue->user, 'trashed') && $issue->user->trashed()))
                                            {{ trim($issue->user->first_name . ' ' . ($issue->user->middle_name ?? '') . ' ' . $issue->user->last_name) }}
                                        @elseif ($issue->user)
                                            {{ trim($issue->user->first_name . ' ' . ($issue->user->middle_name ?? '') . ' ' . $issue->user->last_name) }}
                                            @if ($showRestore)
                                                <span class="text-red-600 font-semibold"> (Deleted)</span>
                                            @endif
                                        @else
                                            <span class="text-gray-500 italic">User Deleted</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-500 mt-0.5">
                                        @if ($issue->user && !(method_exists($issue->user, 'trashed') && $issue->user->trashed()))
                                            &#64;{{ $issue->user->username }}
                                        @elseif ($issue->user)
                                            &#64;{{ $issue->user->username }}
                                        @else
                                            <span class="text-gray-500 italic">@user-deleted</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900">
                                        @if ($issue->slip_id)
                                            @if ($issue->slip && !(method_exists($issue->slip, 'trashed') && $issue->slip->trashed()))
                                                <button wire:click="openSlipDetailsModal({{ $issue->slip->id }})"
                                                    class="text-blue-600 hover:text-blue-800 hover:underline transition-colors duration-150 hover:cursor-pointer cursor-pointer">
                                                    Slip: {{ $issue->slip->slip_id ?? 'N/A' }}
                                                </button>
                                            @elseif ($issue->slip)
                                                <span class="text-gray-900 font-semibold">Slip: {{ $issue->slip->slip_id ?? $issue->slip_id }}@if ($showRestore) <span class="text-gray-500 italic">(deleted)</span>@endif</span>
                                            @else
                                                <span class="text-gray-900 font-semibold">Slip: {{ $issue->slip_id }}@if ($showRestore) <span class="text-gray-500 italic">(deleted)</span>@endif</span>
                                            @endif
                                        @else
                                            <span class="text-gray-500 italic">Miscellaneous</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($issue->resolved_at)
                                        <div class="flex flex-col">
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 w-fit">
                                                Resolved
                                            </span>
                                            <div class="text-xs text-gray-500 mt-1">
                                                {{ $issue->resolved_at->format('M d, Y h:i A') }}
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
                                    @if ($showRestore && $showDeleted)
                                        <x-buttons.submit-button wire:click="openRestoreModal({{ $issue->id }})"
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
                                            <button wire:click="openDetailsModal({{ $issue->id }})"
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

                                            @if ($showRestore)
                                            <button wire:click="openDeleteConfirmation({{ $issue->id }})"
                                                class="hover:cursor-pointer inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                Delete
                                            </button>
                                            @endif
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
                                        <p class="text-gray-500 text-sm">No issues found</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination Footer --}}
            <div class="px-6 bg-gray-50 border-t border-gray-200">
                <x-buttons.nav-pagination :paginator="$issues" />
            </div>
        </div>

        {{-- Restore Modal - Only for super-admin --}}
        @if ($showRestore)
        <livewire:shared.issues.restore :config="['minUserType' => $minUserType]" />
        @endif

        {{-- View Details Modal (Issue) or Slip Details Modal --}}
        @if ($showDetailsModal && $selectedIssue && !$selectedSlip)
            @php
                $isResolved = $selectedIssue->resolved_at !== null;
                $headerClass = $isResolved ? 'border-t-4 border-t-green-500 bg-green-50' : 'border-t-4 border-t-red-500 bg-red-50';
            @endphp
            <x-modals.modal-template show="showDetailsModal" title="ISSUE DETAILS" max-width="max-w-3xl" header-class="{{ $headerClass }}">
                @if ($selectedIssue)
                    <div class="border-b border-gray-200 px-6 py-2 bg-gray-50 -mx-6 -mt-6 mb-2">
                        <div class="grid grid-cols-[1fr_1fr] gap-4 items-start text-xs">
                            <div>
                                <div class="font-semibold text-gray-500 mb-0.5">Date:</div>
                                <div class="text-gray-900">{{ $selectedIssue->created_at->format('M d, Y') }}</div>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-500 mb-0.5">
                                    @if ($selectedIssue->slip_id)
                                        Slip No:
                                    @else
                                        Type:
                                    @endif
                                </div>
                                <div class="text-gray-900 font-semibold">
                                    @if ($selectedIssue->slip_id)
                                        {{ $selectedIssue->slip->slip_id ?? 'N/A' }}
                                    @else
                                        <span class="italic font-normal">Miscellaneous</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-0 -mx-6">
                        <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-white">
                            <div class="font-semibold text-gray-500">Name:</div>
                            <div class="text-gray-900">
                                @if ($selectedIssue->user)
                                    {{ trim($selectedIssue->user->first_name . ' ' . ($selectedIssue->user->middle_name ?? '') . ' ' . $selectedIssue->user->last_name) }}
                                    @if ($showRestore && $selectedIssue->user->trashed())
                                        <span class="text-red-600 font-semibold"> (Deleted)</span>
                                    @endif
                                    <div class="text-xs text-gray-500 mt-0.5">
                                        &#64;{{ $selectedIssue->user->username }}
                                    </div>
                                @else
                                    <span class="text-gray-500 italic">User Deleted</span>
                                    <div class="text-xs text-gray-500 mt-0.5"><span class="text-gray-500 italic">@user-deleted</span></div>
                                @endif
                            </div>
                        </div>
                        <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-gray-100">
                            <div class="font-semibold text-gray-500">Type:</div>
                            <div class="text-gray-900">
                                @if ($selectedIssue->slip_id)
                                    @if ($selectedIssue->slip && !(method_exists($selectedIssue->slip, 'trashed') && $selectedIssue->slip->trashed()))
                                        <span class="text-gray-900 font-semibold">Slip: {{ $selectedIssue->slip->slip_id ?? 'N/A' }}</span>
                                    @elseif ($selectedIssue->slip)
                                        <span class="text-gray-900 font-semibold">Slip: {{ $selectedIssue->slip->slip_id ?? $selectedIssue->slip_id }}@if ($showRestore) <span class="text-gray-500 italic">(deleted)</span>@endif</span>
                                    @else
                                        <span class="text-gray-900 font-semibold">Slip: {{ $selectedIssue->slip_id }}@if ($showRestore) <span class="text-gray-500 italic">(deleted)</span>@endif</span>
                                    @endif
                                @else
                                    <span class="text-gray-500 italic">Miscellaneous</span>
                                @endif
                            </div>
                        </div>
                        <div class="grid grid-cols-[1fr_2fr] gap-4 px-6 py-2 text-xs bg-white">
                            <div class="font-semibold text-gray-500">Description:</div>
                            <div class="text-gray-900 wrap-break-words min-w-0" style="word-break: break-word; overflow-wrap: break-word;">
                                <div class="whitespace-pre-wrap">{{ $selectedIssue->description ?? 'No description provided.' }}</div>
                            </div>
                        </div>
                    </div>
                    @if ($selectedIssue->resolved_at)
                        <div class="border-t border-gray-200 px-6 py-2 bg-gray-50 -mx-6 -mb-6 mt-2">
                            <div class="grid grid-cols-2 gap-4 text-xs">
                                <div>
                                    <div class="font-bold text-gray-500 mb-0.5">Resolved by:</div>
                                    <div>
                                        @if ($selectedIssue->resolvedBy)
                                            <span class="text-gray-900">{{ trim($selectedIssue->resolvedBy->first_name . ' ' . ($selectedIssue->resolvedBy->middle_name ?? '') . ' ' . $selectedIssue->resolvedBy->last_name) }}</span>
                                            @if ($showRestore && $selectedIssue->resolvedBy->trashed())
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
                                        {{ $selectedIssue->resolved_at->format('M d, Y - h:i A') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @else
                    <p class="text-gray-500 text-center">No details available.</p>
                @endif
                <x-slot name="footer">
                    <div class="flex justify-end w-full gap-2">
                        <x-buttons.submit-button wire:click="closeDetailsModal" color="white">
                            Close
                        </x-buttons.submit-button>
                        @if (!$selectedIssue->resolved_at)
                            <x-buttons.submit-button wire:click.prevent="resolveIssue" color="green" wire:loading.attr="disabled" wire:target="resolveIssue"
                                x-bind:disabled="$wire.isResolving">
                                <span wire:loading.remove wire:target="resolveIssue">Resolve</span>
                                <span wire:loading.inline-flex wire:target="resolveIssue" class="inline-flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Resolving...
                                </span>
                            </x-buttons.submit-button>
                        @else
                            <x-buttons.submit-button wire:click.prevent="unresolveIssue" color="orange" wire:loading.attr="disabled" wire:target="unresolveIssue"
                                x-bind:disabled="$wire.isResolving">
                                <span wire:loading.remove wire:target="unresolveIssue">Unresolve</span>
                                <span wire:loading.inline-flex wire:target="unresolveIssue" class="inline-flex items-center gap-2">
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

        {{-- Filter Modal --}}
        <x-modals.filter-modal>
            <x-slot name="filters">
                @if ($showRestore && $showDeleted)
                    <x-modals.filter-restore-body />
                @elseif ($showRestore)
                    <x-modals.filter-superadmin-issues-body :availableStatuses="$availableStatuses" :filterResolved="$filterResolved" :filterIssueType="$filterIssueType" :excludeDeletedItems="$excludeDeletedItems" />
                @else
                    <x-modals.filter-issues-body :availableStatuses="$availableStatuses" :filterResolved="$filterResolved" :filterIssueType="$filterIssueType" />
                @endif
            </x-slot>
        </x-modals.filter-modal>

        {{-- Slip Details Modal --}}
        @include('livewire.shared.slip-details-modal')

        {{-- Edit Modal --}}
        <livewire:shared.issues.edit :config="['minUserType' => $minUserType]" />

        {{-- Slip Delete Modal --}}
        <livewire:shared.slips.delete :config="['minUserType' => $minUserType]" />

        {{-- Issue Delete Modal - Only for super-admin --}}
        @if ($showRestore)
        <livewire:shared.issues.delete :config="['minUserType' => $minUserType]" />
        @endif
    </div>
</div>
