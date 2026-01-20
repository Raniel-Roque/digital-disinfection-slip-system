<div class="min-h-screen bg-gray-50 p-6" @if (
    !$showFilters &&
        !($config['showRestore'] && ($showDeleted ?? false)) &&
        !$showFilters) wire:poll.keep-alive @endif>
    <div class="max-w-7xl mx-auto">
        {{-- Simple Header --}}
        <div class="mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Guards</h1>
                    <p class="text-gray-600 text-sm mt-1">Manage all guards in the system</p>
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
                            placeholder="Search by name or @username...">
                        
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

                    {{-- Desktop: Create Button (Primary action - Icon + Text) --}}
                    @if (!($config['showRestore'] && ($showDeleted ?? false)))
                        <div class="hidden md:block">
                            <x-buttons.submit-button wire:click="openCreateModal" color="blue" size="lg"
                                :fullWidth="false">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                                    </path>
                                </svg>
                                Create
                            </x-buttons.submit-button>
                        </div>
                    @endif

                    {{-- Desktop: Restore Button (Icon + Text) - Only if showRestore is enabled --}}
                    @if ($config['showRestore'])
                    <div class="hidden md:block">
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
                    </div>
                    @endif

                    {{-- Mobile: Restore/Back to Active Button (only when in restore mode) --}}
                    @if ($config['showRestore'] && $showDeleted)
                        <div class="md:hidden">
                            <button wire:click="toggleDeletedView" wire:loading.attr="disabled" wire:target="toggleDeletedView"
                                class="inline-flex items-center px-4 py-2.5 bg-gray-600 hover:bg-gray-700 text-white rounded-lg text-sm font-medium transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 disabled:opacity-50 disabled:cursor-not-allowed hover:cursor-pointer cursor-pointer">
                            <svg wire:loading.remove wire:target="toggleDeletedView" class="w-5 h-5 mr-2" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                </path>
                            </svg>

                            <span wire:loading.remove wire:target="toggleDeletedView">Back to Active</span>
                            <span wire:loading.inline-flex wire:target="toggleDeletedView" class="inline-flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Loading...
                            </span>
                        </button>
                        </div>
                    @endif

                    {{-- Export Button with Create and Restore options (mobile, only when NOT in restore mode) --}}
                    @if (!($config['showRestore'] && ($showDeleted ?? false)))
                        <x-buttons.export-button :showCreate="true" :showRestore="$config['showRestore']" :showDeleted="false" />
                    @endif
                </div>
            </div>

            {{-- Active Filters Display --}}
            @if ($filtersActive && !($config['showRestore'] && ($showDeleted ?? false)))
                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="text-sm text-gray-600">Active filters:</span>

                    @if ($appliedStatus !== null)
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            Status: {{ $availableStatuses[(int) $appliedStatus] ?? '' }}
                            <button wire:click="removeFilter('status')"
                                class="ml-1.5 inline-flex items-center hover:cursor-pointer">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                        clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </span>
                    @endif

                    @if ($config['showGuardTypeFilter'] && $appliedGuardType !== null)
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            Type: {{ $availableGuardTypes[(int) $appliedGuardType] ?? '' }}
                            <button wire:click="removeFilter('guardType')"
                                class="ml-1.5 inline-flex items-center hover:cursor-pointer">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                        clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </span>
                    @endif

                    @if ($appliedCreatedFrom)
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            From: {{ \Carbon\Carbon::parse($appliedCreatedFrom)->format('M d, Y') }}
                            <button wire:click="removeFilter('createdFrom')"
                                class="ml-1.5 inline-flex items-center hover:cursor-pointer">
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
                            To: {{ \Carbon\Carbon::parse($appliedCreatedTo)->format('M d, Y') }}
                            <button wire:click="removeFilter('createdTo')"
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
            @elseif (($appliedCreatedFrom || $appliedCreatedTo) && ($config['showRestore'] && ($showDeleted ?? false)))
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
                                <div class="inline-flex items-center gap-2">
                                    <span>Name</span>
                                    <div
                                        class="inline-flex items-center bg-gray-100 rounded-lg p-0.5 border border-gray-200">
                                        <button wire:click.prevent="applySort('first_name')" type="button"
                                            class="px-2 py-1 text-xs font-medium rounded transition-all duration-150 flex items-center gap-1 hover:cursor-pointer cursor-pointer {{ $this->getSortDirection('first_name') ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}"
                                            title="Sort by First Name">
                                            <span>First</span>
                                            @php
                                                $firstDir = $this->getSortDirection('first_name');
                                            @endphp
                                            @if ($firstDir === 'asc')
                                                <svg class="w-3 h-3 text-green-600" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M5 15l7-7 7 7" />
                                                </svg>
                                            @elseif ($firstDir === 'desc')
                                                <svg class="w-3 h-3 text-red-600" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            @endif
                                        </button>
                                        <div class="w-px h-4 bg-gray-300 mx-0.5"></div>
                                        <button wire:click.prevent="applySort('last_name')" type="button"
                                            class="px-2 py-1 text-xs font-medium rounded transition-all duration-150 flex items-center gap-1 hover:cursor-pointer cursor-pointer {{ $this->getSortDirection('last_name') ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}"
                                            title="Sort by Last Name">
                                            <span>Last</span>
                                            @php
                                                $lastDir = $this->getSortDirection('last_name');
                                            @endphp
                                            @if ($lastDir === 'asc')
                                                <svg class="w-3 h-3 text-green-600" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M5 15l7-7 7 7" />
                                                </svg>
                                            @elseif ($lastDir === 'desc')
                                                <svg class="w-3 h-3 text-red-600" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            @endif
                                        </button>
                                    </div>
                                </div>
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <div class="inline-flex items-center gap-2">
                                    <span>Username</span>
                                    <button wire:click.prevent="applySort('username')" type="button"
                                        class="inline-flex flex-col items-center text-gray-500 hover:text-gray-700 focus:outline-none focus:text-gray-700 transition-colors p-0.5 rounded hover:bg-gray-200 hover:cursor-pointer cursor-pointer"
                                        title="Sort by Username">
                                        @php
                                            $usernameDir = $this->getSortDirection('username');
                                        @endphp
                                        @if ($usernameDir === 'asc')
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
                                        @elseif ($usernameDir === 'desc')
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
                                        @else
                                            <svg class="w-3 h-3 text-gray-400" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="2" d="M5 15l7-7 7 7" />
                                            </svg>
                                            <svg class="w-3 h-3 text-gray-400" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        @endif
                                    </button>
                                </div>
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <div class="inline-flex items-center gap-2">
                                    <span>{{ ($config['showRestore'] && ($showDeleted ?? false)) ? 'Deleted Date' : 'Created Date' }}</span>
                                    @if (!($config['showRestore'] && ($showDeleted ?? false)))
                                        <button wire:click.prevent="applySort('created_at')" type="button"
                                            class="inline-flex flex-col items-center text-gray-500 hover:text-gray-700 focus:outline-none focus:text-gray-700 transition-colors p-0.5 rounded hover:bg-gray-200 hover:cursor-pointer cursor-pointer"
                                            title="Sort by Created Date">
                                            @php
                                                $dateDir = $this->getSortDirection('created_at');
                                            @endphp
                                            @if ($dateDir === 'asc')
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
                                            @elseif ($dateDir === 'desc')
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
                                            @else
                                                <svg class="w-3 h-3 text-gray-400" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M5 15l7-7 7 7" />
                                                </svg>
                                                <svg class="w-3 h-3 text-gray-400" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            @endif
                                        </button>
                                    @endif
                                </div>
                            </th>
                            @if (!($config['showRestore'] && ($showDeleted ?? false)) && $config['showGuardTypeFilter'])
                            <th scope="col"
                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Super Guard
                            </th>
                            @endif
                            <th scope="col"
                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($users as $user)
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900">
                                        {{ $user->first_name }}
                                        @if ($user->middle_name)
                                            {{ $user->middle_name }}
                                        @endif
                                        {{ $user->last_name }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-700">
                                        <span>@</span>{{ $user->username }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900">
                                        @if ($config['showRestore'] && ($showDeleted ?? false))
                                            {{ \Carbon\Carbon::parse($user->deleted_at)->format('M d, Y') }}
                                        @else
                                            {{ \Carbon\Carbon::parse($user->created_at)->format('M d, Y') }}
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-500 mt-0.5">
                                        @if ($config['showRestore'] && ($showDeleted ?? false))
                                            {{ \Carbon\Carbon::parse($user->deleted_at)->format('h:i A') }}
                                        @else
                                            {{ \Carbon\Carbon::parse($user->created_at)->format('h:i A') }}
                                        @endif
                                    </div>
                                </td>
                                @if (!($config['showRestore'] && ($showDeleted ?? false)) && $config['showGuardTypeFilter'])
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    @if ($user->super_guard)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Super Guard
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            Regular Guard
                                        </span>
                                    @endif
                                </td>
                                @endif
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    @if ($config['showRestore'] && ($showDeleted ?? false))
                                        <x-buttons.submit-button wire:click="openRestoreModal({{ $user->id }})"
                                            color="green" size="sm" :fullWidth="false">
                                            <div class="inline-flex items-center gap-1.5">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                                </path>
                                            </svg>
                                                <span>Restore</span>
                                            </div>
                                        </x-buttons.submit-button>
                                    @else
                                        <div class="flex items-center justify-center gap-2">
                                            <x-buttons.submit-button wire:click="openEditModal({{ $user->id }})"
                                                color="blue" size="sm" :fullWidth="false">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                    </path>
                                                </svg>
                                                Edit
                                            </x-buttons.submit-button>
                                            <x-buttons.submit-button
                                                wire:click="openResetPasswordModal({{ $user->id }})"
                                                color="gray" size="sm" :fullWidth="false">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z">
                                                    </path>
                                                </svg>
                                                Reset Password
                                            </x-buttons.submit-button>
                                            @if ($user->disabled)
                                                <x-buttons.submit-button
                                                    wire:click="openDisableModal({{ $user->id }})" color="green"
                                                    size="sm" :fullWidth="false">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z">
                                                        </path>
                                                    </svg>
                                                    Enable
                                                </x-buttons.submit-button>
                                            @else
                                                <x-buttons.submit-button
                                                    wire:click="openDisableModal({{ $user->id }})" color="orange"
                                                    size="sm" :fullWidth="false">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636">
                                                        </path>
                                                    </svg>
                                                    Disable
                                                </x-buttons.submit-button>
                                            @endif
                                            @if ($config['role'] === 'superadmin')
                                            <x-buttons.submit-button wire:click="openDeleteModal({{ $user->id }})"
                                                color="red" size="sm" :fullWidth="false">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                    </path>
                                                </svg>
                                                Delete
                                            </x-buttons.submit-button>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ ($config['showRestore'] && ($showDeleted ?? false)) || !$config['showGuardTypeFilter'] ? '4' : '5' }}" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                                            </path>
                                        </svg>
                                        <h3 class="text-sm font-medium text-gray-900 mb-1">No guards found</h3>
                                        <p class="text-sm text-gray-500">
                                            @if ($search)
                                                No results match your search "<span
                                                    class="font-medium text-gray-700">{{ $search }}</span>".
                                            @else
                                                No guards available in the system.
                                            @endif
                                        </p>
                                        @if ($search)
                                            <button wire:click="$set('search', '')"
                                                class="mt-4 inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors duration-150">
                                                Clear search
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination Footer --}}
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                <x-buttons.nav-pagination :paginator="$users" />
            </div>
        </div>

        {{-- Filter Modal --}}
        <x-modals.filter-modal>
            <x-slot name="filters">
                <div class="space-y-4">
                    {{-- Status Filter -- Disabled when in restore mode --}}
                    <div class="{{ ($config['showRestore'] && ($showDeleted ?? false)) ? 'opacity-50 pointer-events-none' : '' }}">
                        <x-filters.status-dropdown 
                            label="Status"
                            wireModel="filterStatus"
                            :options="$availableStatuses"
                            placeholder="Select status"
                        />
                    </div>

                    {{-- Guard Type Filter -- Disabled when in restore mode --}}
                    @if ($config['showGuardTypeFilter'])
                    <div class="{{ ($config['showRestore'] && ($showDeleted ?? false)) ? 'opacity-50 pointer-events-none' : '' }}">
                        <x-filters.status-dropdown 
                            label="Guard Type"
                            wireModel="filterGuardType"
                            :options="$availableGuardTypes"
                            placeholder="Select guard type"
                        />
                    </div>
                    @endif

                    {{-- Date Filters -- Always active, filter by created_at or deleted_at based on mode --}}
                    <div x-data="{
                        updateToDateMin() {
                            const toInput = $el.closest('.space-y-4').querySelector('[x-ref=&quot;toDateInput&quot;]');
                            const fromInput = $el.querySelector('[x-ref=&quot;fromDateInput&quot;]');
                            if (toInput && fromInput && fromInput.value) {
                                toInput.min = fromInput.value;
                                if (toInput.value && toInput.value < fromInput.value) {
                                    toInput.value = '';
                                    $wire.set('filterCreatedTo', '');
                                }
                            } else {
                                toInput.min = '';
                            }
                        }
                    }">
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ ($config['showRestore'] && ($showDeleted ?? false)) ? 'Deleted From Date' : 'From Date' }}</label>
                        <input type="date" wire:model.live="filterCreatedFrom"
                            x-ref="fromDateInput"
                            @input="
                                const toInput = $refs.toDateInput;
                                if (toInput) {
                                    if (toInput.value && $el.value && toInput.value < $el.value) {
                                        toInput.value = '';
                                        $wire.set('filterCreatedTo', '');
                                    }
                                }
                            "
                            max="<?php echo date('Y-m-d'); ?>"
                            class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ ($config['showRestore'] && ($showDeleted ?? false)) ? 'Deleted To Date' : 'To Date' }}</label>
                        <input type="date" wire:model.live="filterCreatedTo"
                            x-ref="toDateInput"
                            @input="
                                const fromInput = $refs.fromDateInput;
                                if (fromInput) {
                                    if (fromInput.value && $el.value && fromInput.value > $el.value) {
                                        fromInput.value = '';
                                        $wire.set('filterCreatedFrom', '');
                                    }
                                }
                            "
                            max="<?php echo date('Y-m-d'); ?>"
                            class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-blue-500">
                    </div>
                </div>
            </x-slot>
        </x-modals.filter-modal>

        {{-- Edit Modal --}}
        <livewire:shared.guards.edit :config="$config" />

        {{-- Disable/Enable Confirmation Modal --}}
        <livewire:shared.guards.disable :config="$config" />

        {{-- Reset Password Modal --}}
        <livewire:shared.guards.reset-password />

        {{-- Create Guard Modal --}}
        <livewire:shared.guards.create :config="$config" />

        {{-- Delete Confirmation Modal - Only for superadmin --}}
        @if ($config['role'] === 'superadmin')
            <livewire:shared.guards.delete />
        @endif

        {{-- Restore Modal --}}
        @if ($config['showRestore'])
            <livewire:shared.guards.restore :config="['minUserType' => 2]" />
        @endif
    </div>
</div>

<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('open-print-window', (event) => {
            const url = event.url || (Array.isArray(event) ? event[0]?.url : null);
            if (url) {
                window.open(url, '_blank');
            }
        });
    });
</script>
