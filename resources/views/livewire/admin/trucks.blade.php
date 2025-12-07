<div class="min-h-screen bg-gray-50 p-6">
    <div class="max-w-7xl mx-auto">

        {{-- Simple Header --}}
        <div class="mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Trucks from Disinfection Slips</h1>
                    <p class="text-gray-600 text-sm mt-1">View all trucks associated with disinfection slips</p>
                </div>

                {{-- Search and Filter Bar --}}
                <div class="flex gap-3 w-full lg:w-auto">
                    {{-- Search Bar --}}
                    <div class="relative flex-1 lg:w-96">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" wire:model.live="search"
                            class="block w-full pl-10 pr-10 py-2.5 bg-white border border-gray-300 rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                            placeholder="Search...">
                        @if ($search)
                            <button wire:click="$set('search', '')"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        @endif
                    </div>

                    {{-- Filter Button --}}
                    <button wire:click="$toggle('showFilters')"
                        class="inline-flex items-center px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 relative">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z">
                            </path>
                        </svg>
                        Filters
                    </button>

                    {{-- Create Button --}}
                    <button wire:click="openCreateModal"
                        class="inline-flex items-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                            </path>
                        </svg>
                        Create Slip
                    </button>
                </div>
            </div>

            {{-- Active Filters Display --}}
            @if ($filtersActive)
                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="text-sm text-gray-600">Active filters:</span>

                    @if ($appliedStatus !== null)
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            Status: {{ $availableStatuses[$appliedStatus] }}
                            <button wire:click="removeFilter('status')" class="ml-1.5 inline-flex items-center">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                        clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </span>
                    @endif

                    @if (!empty($appliedOrigin))
                        @foreach ($appliedOrigin as $originId)
                            <span
                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Origin: {{ $locations->find($originId)->location_name }}
                                <button wire:click="removeSpecificFilter('origin', {{ $originId }})"
                                    class="ml-1.5 inline-flex items-center">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </span>
                        @endforeach
                    @endif

                    @if (!empty($appliedDestination))
                        @foreach ($appliedDestination as $destinationId)
                            <span
                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Destination: {{ $locations->find($destinationId)->location_name }}
                                <button wire:click="removeSpecificFilter('destination', {{ $destinationId }})"
                                    class="ml-1.5 inline-flex items-center">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </span>
                        @endforeach
                    @endif

                    @if (!empty($appliedDriver))
                        @foreach ($appliedDriver as $driverId)
                            @php
                                $driver = $drivers->find($driverId);
                            @endphp
                            <span
                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Driver: {{ $driver->first_name }} {{ $driver->last_name }}
                                <button wire:click="removeSpecificFilter('driver', {{ $driverId }})"
                                    class="ml-1.5 inline-flex items-center">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </span>
                        @endforeach
                    @endif

                    @if (!empty($appliedPlateNumber))
                        @foreach ($appliedPlateNumber as $truckId)
                            @php
                                $truck = $trucks->find($truckId);
                            @endphp
                            <span
                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Plate: {{ $truck->plate_number }}
                                <button wire:click="removeSpecificFilter('plateNumber', {{ $truckId }})"
                                    class="ml-1.5 inline-flex items-center">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </span>
                        @endforeach
                    @endif

                    @if (!empty($appliedHatcheryGuard))
                        @foreach ($appliedHatcheryGuard as $guardId)
                            @php
                                $guard = $guards->find($guardId);
                            @endphp
                            <span
                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Hatchery Guard:
                                {{ trim("{$guard->first_name} {$guard->middle_name} {$guard->last_name}") }}
                                <button wire:click="removeSpecificFilter('hatcheryGuard', {{ $guardId }})"
                                    class="ml-1.5 inline-flex items-center">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </span>
                        @endforeach
                    @endif

                    @if (!empty($appliedReceivedGuard))
                        @foreach ($appliedReceivedGuard as $guardId)
                            @php
                                $guard = $guards->find($guardId);
                            @endphp
                            <span
                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Received Guard:
                                {{ trim("{$guard->first_name} {$guard->middle_name} {$guard->last_name}") }}
                                <button wire:click="removeSpecificFilter('receivedGuard', {{ $guardId }})"
                                    class="ml-1.5 inline-flex items-center">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </span>
                        @endforeach
                    @endif

                    @if ($appliedCreatedFrom)
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            From: {{ \Carbon\Carbon::parse($appliedCreatedFrom)->format('M d, Y') }}
                            <button wire:click="removeFilter('createdFrom')" class="ml-1.5 inline-flex items-center">
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
                            <button wire:click="removeFilter('createdTo')" class="ml-1.5 inline-flex items-center">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                        clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </span>
                    @endif

                    <button wire:click="clearFilters"
                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-100 transition-colors">
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
                                <div class="flex items-center gap-2">
                                    <span>Slip No.</span>
                                    <button wire:click.prevent="applySort('slip_id')" type="button"
                                        class="inline-flex flex-col items-center text-gray-500 hover:text-gray-700 focus:outline-none focus:text-gray-700 transition-colors p-0.5 rounded hover:bg-gray-200"
                                        title="Sort by Slip Number">
                                        @if ($sortBy === 'slip_id')
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
                                        @elseif ($sortBy === 'created_at')
                                            {{-- Show current sort state when sorting by created_at (default) --}}
                                            @if ($sortDirection === 'desc')
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
                                Vehicle
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Origin
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Destination
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($slips as $slip)
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900">
                                        {{ $slip->slip_id }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-0.5">
                                        {{ \Carbon\Carbon::parse($slip->created_at)->format('M d, Y h:i A') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        // Determine what to show first based on search/filter
                                        $showDriverFirst = false;
                                        $showPlateFirst = true;

                                        // Check if searching for driver name
                                        if ($search) {
                                            $driverName = strtolower(
                                                $slip->driver->first_name . ' ' . $slip->driver->last_name,
                                            );
                                            $searchLower = strtolower($search);
                                            if (str_contains($driverName, $searchLower)) {
                                                $showDriverFirst = true;
                                                $showPlateFirst = false;
                                            }
                                        }

                                        // Check if filtering by driver (takes precedence if both search and filter)
                                        if (!empty($appliedDriver)) {
                                            $showDriverFirst = true;
                                            $showPlateFirst = false;
                                        }
                                    @endphp

                                    @if ($showDriverFirst)
                                        <div class="text-sm font-semibold text-gray-900">
                                            {{ $slip->driver->first_name }} {{ $slip->driver->last_name }}
                                        </div>
                                        <div class="text-xs text-gray-500 mt-0.5">
                                            {{ $slip->truck->plate_number }}
                                        </div>
                                    @else
                                        <div class="text-sm font-semibold text-gray-900">
                                            {{ $slip->truck->plate_number }}
                                        </div>
                                        <div class="text-xs text-gray-500 mt-0.5">
                                            {{ $slip->driver->first_name }} {{ $slip->driver->last_name }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-700">
                                        {{ $slip->location->location_name }}
                                    </div>
                                    @if ($slip->hatcheryGuard)
                                        <div class="text-xs text-gray-500 mt-0.5">
                                            {{ trim("{$slip->hatcheryGuard->first_name} {$slip->hatcheryGuard->middle_name} {$slip->hatcheryGuard->last_name}") }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-700">
                                        {{ $slip->destination->location_name }}
                                    </div>
                                    @if ($slip->receivedGuard)
                                        <div class="text-xs text-gray-500 mt-0.5">
                                            {{ trim("{$slip->receivedGuard->first_name} {$slip->receivedGuard->middle_name} {$slip->receivedGuard->last_name}") }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($slip->status == 0)
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            Ongoing
                                        </span>
                                    @elseif($slip->status == 1)
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            Disinfecting
                                        </span>
                                    @elseif($slip->status == 2)
                                        <div>
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Completed
                                            </span>
                                            @if ($slip->completed_at)
                                                <div class="text-xs text-gray-500 mt-0.5">
                                                    {{ \Carbon\Carbon::parse($slip->completed_at)->format('M d, Y h:i A') }}
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <button wire:click="openDetailsModal({{ $slip->id }})"
                                        class="hover:cursor-pointer inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        View Details
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4">
                                            </path>
                                        </svg>
                                        <h3 class="text-sm font-medium text-gray-900 mb-1">No disinfection slips found
                                        </h3>
                                        <p class="text-sm text-gray-500">
                                            @if ($search)
                                                No results match your search "<span
                                                    class="font-medium text-gray-700">{{ $search }}</span>".
                                            @else
                                                Get started by creating a new disinfection slip.
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
            <div class="px-6 bg-gray-50 border-t border-gray-200">
                <x-buttons.nav-pagination :paginator="$slips" />
            </div>
        </div>

        {{-- Filter Modal --}}
        <x-modals.filter-modal>
            <x-slot name="filters">
                <x-modals.filter-admin-body :availableStatuses="$availableStatuses" :locations="$locations" :drivers="$drivers" :trucks="$trucks"
                    :filterTruckOptions="$filterTruckOptions" :filterDriverOptions="$filterDriverOptions" :filterHatcheryGuardOptions="$filterHatcheryGuardOptions" :filterReceivedGuardOptions="$filterReceivedGuardOptions" :filterOriginOptions="$filterOriginOptions"
                    :filterDestinationOptions="$filterDestinationOptions" :filterStatus="$filterStatus" />
            </x-slot>
        </x-modals.filter-modal>

        {{-- Admin Slip Details Modal --}}
        @include('livewire.admin.slip-details-modal')

        {{-- Admin Create Modal --}}
        <x-modals.admin-slip-creation-modal :trucks="$trucks" :locations="$locations" :drivers="$drivers" :guards="$guards"
            :available-origins-options="$availableOriginsOptions" :available-destinations-options="$availableDestinationsOptions" :create-truck-options="$createTruckOptions" :create-driver-options="$createDriverOptions" :create-guard-options="$createGuardOptions"
            :create-received-guard-options="$createReceivedGuardOptions" />

        {{-- Admin Edit Modal --}}
        @if ($selectedSlip)
            <x-modals.admin-slip-edit-modal :trucks="$trucks" :locations="$locations" :drivers="$drivers" :guards="$guards"
                :available-origins-options="$editAvailableOriginsOptions" :available-destinations-options="$editAvailableDestinationsOptions" :edit-truck-options="$editTruckOptions" :edit-driver-options="$editDriverOptions" :edit-guard-options="$editGuardOptions"
                :edit-received-guard-options="$editReceivedGuardOptions" :slip-status="$selectedSlip->status" :selected-slip="$selectedSlip" />
        @endif

    </div>
</div>
