<div class="min-h-screen bg-gray-50 p-6" @if (!$showFilters && !$showCreateModal && !$showEditModal && !$showDisableModal && !$showDeleteModal && !$showRestoreModal) wire:poll.keep-alive @endif>
    <div class="max-w-7xl mx-auto">
        {{-- Simple Header --}}
        <div class="mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Plate Numbers</h1>
                    <p class="text-gray-600 text-sm mt-1">Manage all plate numbers in the system</p>
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
                            placeholder="Search by plate number...">
                        
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

                    {{-- Create Button (Primary action - Icon + Text) --}}
                    @if (!($showDeleted ?? false))
                        <x-buttons.submit-button wire:click="openCreateModal" color="blue" size="lg"
                            :fullWidth="false">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                                </path>
                            </svg>
                            Create Plate Number
                        </x-buttons.submit-button>
                    @endif

                    {{-- Restore Deleted Button (Icon + Text) --}}
                    <button wire:click="toggleDeletedView" wire:loading.attr="disabled" wire:target="toggleDeletedView"
                        class="inline-flex items-center px-4 py-2.5 {{ $showDeleted ?? false ? 'bg-gray-600 hover:bg-gray-700' : 'bg-orange-600 hover:bg-orange-700' }} text-white rounded-lg text-sm font-medium transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 {{ $showDeleted ?? false ? 'focus:ring-gray-500' : 'focus:ring-orange-500' }} disabled:opacity-50 disabled:cursor-not-allowed hover:cursor-pointer cursor-pointer">
                        <svg wire:loading.remove wire:target="toggleDeletedView" class="w-5 h-5 mr-2" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                            </path>
                        </svg>

                        <span wire:loading.remove
                            wire:target="toggleDeletedView">{{ $showDeleted ?? false ? 'Back to Active' : 'Restore Deleted' }}</span>
                        <span wire:loading wire:target="toggleDeletedView">Loading...</span>
                    </button>

                    {{-- Download Button (Icon only with dropdown) --}}
                    @if (!($showDeleted ?? false))
                        <x-buttons.export-button />
                    @endif
                </div>
            </div>

            {{-- Active Filters Display --}}
            @if ($filtersActive)
                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="text-sm text-gray-600">Active filters:</span>

                    @if ($appliedStatus !== null)
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            Status: {{ $availableStatuses[(int) $appliedStatus] }}
                            <button wire:click="removeFilter('status')" class="ml-1.5 inline-flex items-center hover:cursor-pointer">
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
                            <button wire:click="removeFilter('createdFrom')" class="ml-1.5 inline-flex items-center hover:cursor-pointer">
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
                            <button wire:click="removeFilter('createdTo')" class="ml-1.5 inline-flex items-center hover:cursor-pointer">
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
                                    <span>Plate Number</span>
                                    <button wire:click.prevent="applySort('plate_number')" type="button"
                                        class="inline-flex flex-col items-center text-gray-500 hover:text-gray-700 focus:outline-none focus:text-gray-700 transition-colors p-0.5 rounded hover:bg-gray-200 hover:cursor-pointer cursor-pointer"
                                        title="Sort by Plate Number">
                                        @php
                                            $plateDir = $this->getSortDirection('plate_number');
                                        @endphp
                                        @if ($plateDir === 'asc')
                                            <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 15l7-7 7 7" />
                                            </svg>
                                            <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        @elseif ($plateDir === 'desc')
                                            <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 15l7-7 7 7" />
                                            </svg>
                                            <svg class="w-3 h-3 text-red-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
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
                                <div class="inline-flex items-center gap-2">
                                    <span>Created Date</span>
                                    <button wire:click.prevent="applySort('created_at')" type="button"
                                        class="inline-flex flex-col items-center text-gray-500 hover:text-gray-700 focus:outline-none focus:text-gray-700 transition-colors p-0.5 rounded hover:bg-gray-200 hover:cursor-pointer cursor-pointer"
                                        title="Sort by Created Date">
                                        @php
                                            $dateDir = $this->getSortDirection('created_at');
                                        @endphp
                                        @if ($dateDir === 'asc')
                                            <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 15l7-7 7 7" />
                                            </svg>
                                            <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        @elseif ($dateDir === 'desc')
                                            <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 15l7-7 7 7" />
                                            </svg>
                                            <svg class="w-3 h-3 text-red-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
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
                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($trucks as $truck)
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900">
                                        {{ $truck->plate_number }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900">
                                        {{ \Carbon\Carbon::parse($truck->created_at)->format('M d, Y') }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-0.5">
                                        {{ \Carbon\Carbon::parse($truck->created_at)->format('h:i A') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    @if ($showDeleted)
                                        <x-buttons.submit-button wire:click="openRestoreModal({{ $truck->id }})"
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
                                            <x-buttons.submit-button wire:click="openEditModal({{ $truck->id }})"
                                                color="blue" size="sm" :fullWidth="false">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                    </path>
                                                </svg>
                                                Edit
                                            </x-buttons.submit-button>
                                            @if ($truck->disabled)
                                                <x-buttons.submit-button
                                                    wire:click="openDisableModal({{ $truck->id }})" color="green"
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
                                                    wire:click="openDisableModal({{ $truck->id }})" color="orange"
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
                                            <x-buttons.submit-button wire:click="openDeleteModal({{ $truck->id }})"
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
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4">
                                            </path>
                                        </svg>
                                        <h3 class="text-sm font-medium text-gray-900 mb-1">No plate numbers found</h3>
                                        <p class="text-sm text-gray-500">
                                            @if ($search)
                                                No results match your search "<span
                                                    class="font-medium text-gray-700">{{ $search }}</span>".
                                            @else
                                                No plate numbers available in the system.
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
                <x-buttons.nav-pagination :paginator="$trucks" />
            </div>
        </div>

        {{-- Filter Modal --}}
        <x-modals.filter-modal>
            <x-slot name="filters">
                <x-modals.filter-plate-numbers-body :availableStatuses="$availableStatuses" />
            </x-slot>
        </x-modals.filter-modal>

        {{-- Edit Modal --}}
        @if ($showEditModal)
            <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
                aria-modal="true">
                {{-- Backdrop --}}
                <div class="fixed inset-0 transition-opacity bg-black/80" wire:click="closeModal"></div>

                {{-- Modal Panel --}}
                <div class="flex min-h-full items-center justify-center p-4">
                    <div
                        class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all w-full max-w-lg">
                        <div class="px-6 py-4 bg-white border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Edit Plate Number</h3>
                        </div>

                        <div class="px-6 py-4">
                    @csrf
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Plate Number <span
                                            class="text-red-500">*</span></label>
                                    <input type="text" wire:model.live="plate_number" maxlength="8"
                                        class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent uppercase"
                                        placeholder="Enter plate number (max 7 non-space characters)">
                                    @error('plate_number')
                                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                            <button wire:click="closeModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 hover:cursor-pointer cursor-pointer">
                                Cancel
                            </button>
                            <button wire:click.prevent="updateTruck" wire:loading.attr="disabled" wire:target="updateTruck"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 hover:cursor-pointer transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                x-bind:disabled="!$wire.hasChanges">
                                <span wire:loading.remove wire:target="updateTruck">Save Changes</span>
                                <span wire:loading wire:target="updateTruck" class="inline-flex items-center gap-2">
                                    Saving...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Disable/Enable Confirmation Modal --}}
        @if ($showDisableModal)
            <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
                aria-modal="true">
                {{-- Backdrop --}}
                <div class="fixed inset-0 transition-opacity bg-black/80" wire:click="closeModal"></div>

                {{-- Modal Panel --}}
                <div class="flex min-h-full items-center justify-center p-4">
                    <div
                        class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all w-full max-w-lg">
                        <div class="px-6 py-4 bg-white border-b border-gray-200">
                            <div class="flex items-center">
                                @if ($selectedTruckDisabled)
                                    <div class="flex items-center justify-center w-12 h-12 bg-green-100 rounded-full">
                                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z">
                                            </path>
                                        </svg>
                                    </div>
                                    <h3 class="ml-4 text-lg font-semibold text-gray-900">Enable Plate Number</h3>
                                @else
                                    <div class="flex items-center justify-center w-12 h-12 bg-red-100 rounded-full">
                                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636">
                                            </path>
                                        </svg>
                                    </div>
                                    <h3 class="ml-4 text-lg font-semibold text-gray-900">Disable Plate Number</h3>
                                @endif
                            </div>
                        </div>

                        <div class="px-6 py-4">
                    @csrf
                            <p class="text-sm text-gray-600">
                                @if ($selectedTruckDisabled)
                                    Are you sure you want to enable this plate number? The plate number will be
                                    available for use again.
                                @else
                                    Are you sure you want to disable this plate number? The plate number will not be
                                    available for use.
                                @endif
                            </p>
                        </div>

                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                            <button wire:click="closeModal" wire:loading.attr="disabled" wire:target="toggleTruckStatus"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 disabled:opacity-50 disabled:cursor-not-allowed hover:cursor-pointer cursor-pointer">
                                Cancel
                            </button>
                            @if ($selectedTruckDisabled)
                                <button wire:click="toggleTruckStatus" wire:loading.attr="disabled" wire:target="toggleTruckStatus"
                                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed hover:cursor-pointer cursor-pointer">
                                    <span wire:loading.remove wire:target="toggleTruckStatus">Enable Plate Number</span>
                                    <span wire:loading wire:target="toggleTruckStatus" class="inline-flex items-center gap-2">
                                        Enabling...
                                    </span>
                                </button>
                            @else
                                <button wire:click="toggleTruckStatus" wire:loading.attr="disabled" wire:target="toggleTruckStatus"
                                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed hover:cursor-pointer cursor-pointer">
                                    <span wire:loading.remove wire:target="toggleTruckStatus">Disable Plate Number</span>
                                    <span wire:loading wire:target="toggleTruckStatus" class="inline-flex items-center gap-2">
                                        Disabling...
                                    </span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Create Plate Number Modal --}}
        @if ($showCreateModal)
            <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
                aria-modal="true">
                {{-- Backdrop --}}
                <div class="fixed inset-0 transition-opacity bg-black/80" wire:click="closeModal"></div>

                {{-- Modal Panel --}}
                <div class="flex min-h-full items-center justify-center p-4">
                    <div
                        class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all w-full max-w-lg">
                        <div class="px-6 py-4 bg-white border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Create Plate Number</h3>
                        </div>

                        <div class="px-6 py-4">
                    @csrf
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Plate Number <span
                                            class="text-red-500">*</span></label>
                                    <input type="text" wire:model="create_plate_number" maxlength="8"
                                        class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent uppercase"
                                        placeholder="Enter plate number (max 7 non-space characters)">
                                    @error('create_plate_number')
                                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                            <button wire:click="closeModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 hover:cursor-pointer cursor-pointer">
                                Cancel
                            </button>
                            <button wire:click.prevent="createTruck" wire:loading.attr="disabled" wire:target="createTruck"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span wire:loading.remove wire:target="createTruck">Create Plate Number</span>
                                <span wire:loading wire:target="createTruck" class="inline-flex items-center gap-2">
                                    Creating...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Delete Confirmation Modal --}}
        <x-modals.delete-modal :show="$showDeleteModal" title="Delete Plate Number" :name="$selectedTruckName" onConfirm="deleteTruck"
            confirmText="Delete Plate Number" />

        {{-- Restore Confirmation Modal --}}
        @if ($showRestoreModal)
            <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
                aria-modal="true">
                {{-- Backdrop --}}
                <div class="fixed inset-0 transition-opacity bg-black/80" wire:click="closeModal"></div>

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
                                <h3 class="ml-4 text-lg font-semibold text-gray-900">Restore Plate Number</h3>
                            </div>
                        </div>

                        <div class="px-6 py-4">
                    @csrf
                            <p class="text-sm text-gray-600">
                                Are you sure you want to restore <span
                                    class="font-medium text-gray-900">{{ $selectedTruckName }}</span>?
                                The plate number will be available for use again.
                            </p>
                        </div>

                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                            <button wire:click="closeModal" wire:loading.attr="disabled" wire:target="restorePlateNumber"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 hover:cursor-pointer cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                                Cancel
                            </button>
                            <button wire:click.prevent="restorePlateNumber" wire:loading.attr="disabled" wire:target="restorePlateNumber"
                                x-bind:disabled="$wire.isRestoring"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed hover:cursor-pointer cursor-pointer">
                                <span wire:loading.remove wire:target="restorePlateNumber">Restore Plate Number</span>
                                <span wire:loading wire:target="restorePlateNumber" class="inline-flex items-center gap-2">
                                    Restoring...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
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
