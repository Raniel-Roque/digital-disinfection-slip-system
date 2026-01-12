<div class="min-h-screen bg-gray-50 p-6" @if (!$showFilters) wire:poll.keep-alive @endif>
    <div class="max-w-7xl mx-auto">
        {{-- Simple Header --}}
        <div class="mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Locations</h1>
                    <p class="text-gray-600 text-sm mt-1">Manage all locations in the system</p>
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
                            placeholder="Search by location name...">
                        
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

                    {{-- Export Button with Create option (mobile) --}}
                    <x-buttons.export-button :showCreate="true" />
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
                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-1/5">
                                Logo
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <div class="inline-flex items-center gap-2">
                                    <span>Location Name</span>
                                    <button wire:click.prevent="applySort('location_name')" type="button"
                                        class="inline-flex flex-col items-center text-gray-500 hover:text-gray-700 focus:outline-none focus:text-gray-700 transition-colors p-0.5 rounded hover:bg-gray-200 hover:cursor-pointer cursor-pointer"
                                        title="Sort by Location Name">
                                        @php
                                            $nameDir = $this->getSortDirection('location_name');
                                        @endphp
                                        @if ($nameDir === 'asc')
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
                                        @elseif ($nameDir === 'desc')
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
                                Create Slip
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-32">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($locations as $location)
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center">
                                        <div class="w-28 h-20">
                                            @if ($location->attachment_id && $location->attachment)
                                                <img src="{{ asset('storage/' . $location->attachment->file_path) }}"
                                                    alt="{{ $location->location_name }}"
                                                    class="max-w-full max-h-full object-contain">
                                            @else
                                                <img src="{{ asset('storage/' . $defaultLogoPath) }}"
                                                    alt="{{ $location->location_name }}"
                                                    class="max-w-full max-h-full object-contain">
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900">
                                        {{ $location->location_name }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-gray-900">
                                        {{ \Carbon\Carbon::parse($location->created_at)->format('M d, Y') }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-0.5">
                                        {{ \Carbon\Carbon::parse($location->created_at)->format('h:i A') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    @if ($location->create_slip)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Allowed
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            Not Allowed
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <div class="flex items-center justify-center gap-2">
                                        <x-buttons.submit-button wire:click="openEditModal({{ $location->id }})"
                                            color="blue" size="sm" :fullWidth="false">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                </path>
                                            </svg>
                                            Edit
                                        </x-buttons.submit-button>
                                        @if ($location->disabled)
                                            <x-buttons.submit-button
                                                wire:click="openDisableModal({{ $location->id }})" color="green"
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
                                                wire:click="openDisableModal({{ $location->id }})" color="orange"
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
                                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                            </path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z">
                                            </path>
                                        </svg>
                                        <h3 class="text-sm font-medium text-gray-900 mb-1">No locations found</h3>
                                        <p class="text-sm text-gray-500">
                                            @if ($search)
                                                No results match your search "<span
                                                    class="font-medium text-gray-700">{{ $search }}</span>".
                                            @else
                                                No locations available in the system.
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
                <x-buttons.nav-pagination :paginator="$locations" />
            </div>
        </div>

        {{-- Filter Modal --}}
        <x-modals.filter-modal>
            <x-slot name="filters">
                <x-modals.filter-locations-body :availableStatuses="$availableStatuses" />
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
                        class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all w-full max-w-lg"
                        wire:click.stop>
                        <div class="px-6 py-4 bg-white border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Edit Location</h3>
                        </div>

                        <div class="px-6 py-4">
                            @csrf
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Location Name <span
                                            class="text-red-500">*</span></label>
                                    <input type="text" wire:model.live="location_name"
                                        class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="Enter location name">
                                    @error('location_name')
                                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                    @enderror
                                </div>

                                {{-- Logo Section --}}
                                <div>
                                    <div class="mb-3">
                                        <label class="text-sm font-medium text-gray-700">Logo <span
                                                class="text-gray-400">(Optional)</span>
                                        
                                        {{-- Remove/Cancel/Clear buttons after Optional --}}
                                        @if ($edit_logo)
                                            {{-- Clear button for newly selected file --}}
                                            <button wire:click="clearLogo('edit')" type="button"
                                                class="ml-2 text-xs text-red-600 hover:text-red-800 hover:cursor-pointer cursor-pointer">
                                                Clear
                                            </button>
                                        @elseif ($this->editLogoPath && $this->editLogoPath !== $defaultLogoPath)
                                            @if (!$remove_logo)
                                                <button wire:click="removeLogo" type="button"
                                                    class="ml-2 text-xs text-red-600 hover:text-red-800 hover:cursor-pointer cursor-pointer">
                                                    Remove Logo
                                                </button>
                                            @else
                                                <button wire:click="cancelRemoveLogo" type="button"
                                                    class="ml-2 text-xs text-blue-600 hover:text-blue-800 hover:cursor-pointer cursor-pointer">
                                                    Cancel Remove
                                                </button>
                                            @endif
                                        @endif
                                        </label>
                                    </div>

                                    <div class="space-y-3">
                                        {{-- Choose Image Button - Hide when file is selected --}}
                                        @if (!$edit_logo)
                                            @if (!$this->editLogoPath || $remove_logo || $this->editLogoPath === $defaultLogoPath)
                                        <label
                                            class="cursor-pointer inline-flex items-center justify-center w-full px-4 py-2.5 bg-white border-2 border-dashed border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:border-blue-400 hover:bg-blue-50 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                                </path>
                                            </svg>
                                            Choose Image
                                            <input type="file" wire:model="edit_logo" class="hidden"
                                                accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                                        </label>
                                            @endif
                                        @endif

                                        {{-- Logo Preview - Only show if there's an actual logo (not default) --}}
                                        @if ($edit_logo)
                                            <div class="flex items-center justify-center bg-white rounded-lg p-4 border border-gray-200 h-48">
                                                <img src="{{ $edit_logo->temporaryUrl() }}" alt="Logo preview"
                                                    class="max-w-full max-h-full w-auto h-auto object-contain">
                                            </div>
                                            
                                            {{-- File Info for newly selected file - Below preview --}}
                                            <div class="bg-gray-50 rounded-lg px-3 py-2 border border-gray-200">
                                                <p class="text-sm text-gray-700 truncate"
                                                    title="{{ $edit_logo->getClientOriginalName() }}">
                                                    {{ $edit_logo->getClientOriginalName() }}
                                                </p>
                                            </div>
                                        @elseif ($this->editLogoPath && !$remove_logo && $this->editLogoPath !== $defaultLogoPath)
                                            <div class="flex items-center justify-center bg-white rounded-lg p-4 border border-gray-200 h-48">
                                                <img src="{{ asset('storage/' . $this->editLogoPath) }}"
                                                    alt="Current logo" class="max-w-full max-h-full w-auto h-auto object-contain">
                                            </div>
                                            
                                            {{-- Combined File Info and Uploaded By Box - Under preview --}}
                                            <div class="bg-gray-50 rounded-lg px-3 py-2 border border-gray-200">
                                                <p class="text-sm text-gray-700 truncate mb-2"
                                                    title="{{ $this->editLogoPath }}">
                                                    Current: {{ basename($this->editLogoPath) }}
                                                </p>
                                                @if ($currentLocation && $currentLocation->attachment_id && $currentLocation->attachment && $currentLocation->attachment->user)
                                                    <div class="text-xs text-gray-500 pt-2 border-t border-gray-200">
                                                        <div class="font-medium text-gray-600">Uploaded by: {{ $currentLocation->attachment->user->first_name }} {{ $currentLocation->attachment->user->last_name }}</div>
                                                        <div class="text-gray-400">({{ $currentLocation->attachment->user->username }})</div>
                                            </div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>

                                    @error('edit_logo')
                                        <span class="text-red-500 text-xs mt-2 block">{{ $message }}</span>
                                    @enderror
                                    <p class="text-xs text-gray-600 mt-3 leading-relaxed">
                                        Supported formats: JPEG, PNG, GIF, WebP (Max 15MB)
                                    </p>
                                </div>

                                {{-- Create Slip Toggle --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Allow Create Slip</label>
                                    <label class="relative inline-flex items-center cursor-pointer" x-data="{ createSlip: @entangle('create_slip').live }" x-init="createSlip = @js($create_slip ?? false)">
                                        <input type="checkbox" x-model="createSlip" :checked="createSlip" class="sr-only">
                                        <div class="w-11 h-6 rounded-full focus-within:outline-none focus-within:ring-4 focus-within:ring-blue-300 transition-colors duration-200 relative" :class="createSlip ? 'bg-blue-600' : 'bg-gray-200'">
                                            <div class="absolute top-[2px] left-[2px] bg-white border border-gray-300 rounded-full h-5 w-5 transition-transform duration-200" :class="createSlip ? 'translate-x-5' : 'translate-x-0'"></div>
                                        </div>
                                        <span class="ml-3 text-sm text-gray-700" x-text="createSlip ? 'Enabled - Guards can create slips on outgoing trucks' : 'Disabled - Guards cannot create slips on outgoing trucks'"></span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                            <button wire:click="closeModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 hover:cursor-pointer cursor-pointer">
                                Cancel
                            </button>
                            <button wire:click.prevent="updateLocation" wire:loading.attr="disabled" wire:target="updateLocation"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 hover:cursor-pointer transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                x-bind:disabled="!$wire.hasChanges">
                                <span wire:loading.remove wire:target="updateLocation">Save Changes</span>
                                <span wire:loading.inline-flex wire:target="updateLocation" class="inline-flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
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
                                @if ($selectedLocationDisabled)
                                    <div class="flex items-center justify-center w-12 h-12 bg-green-100 rounded-full">
                                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z">
                                            </path>
                                        </svg>
                                    </div>
                                    <h3 class="ml-4 text-lg font-semibold text-gray-900">Enable Location</h3>
                                @else
                                    <div class="flex items-center justify-center w-12 h-12 bg-red-100 rounded-full">
                                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636">
                                            </path>
                                        </svg>
                                    </div>
                                    <h3 class="ml-4 text-lg font-semibold text-gray-900">Disable Location</h3>
                                @endif
                            </div>
                        </div>

                        <div class="px-6 py-4">
                    @csrf
                            <p class="text-sm text-gray-600">
                                @if ($selectedLocationDisabled)
                                    Are you sure you want to enable this location? The location will be available for
                                    use again.
                                @else
                                    Are you sure you want to disable this location? The location will not be available
                                    for use.
                                @endif
                            </p>
                        </div>

                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                            <button wire:click="closeModal" wire:loading.attr="disabled" wire:target="toggleLocationStatus"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer hover:cursor-pointer">
                                Cancel
                            </button>
                            @if ($selectedLocationDisabled)
                                <button wire:click="toggleLocationStatus" wire:loading.attr="disabled" wire:target="toggleLocationStatus"
                                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <span wire:loading.remove wire:target="toggleLocationStatus">Enable Location</span>
                                    <span wire:loading.inline-flex wire:target="toggleLocationStatus" class="inline-flex items-center gap-2"><svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Enabling...
                                    </span>
                                </button>
                            @else
                                <button wire:click="toggleLocationStatus" wire:loading.attr="disabled" wire:target="toggleLocationStatus"
                                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <span wire:loading.remove wire:target="toggleLocationStatus">Disable Location</span>
                                    <span wire:loading.inline-flex wire:target="toggleLocationStatus" class="inline-flex items-center gap-2"><svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Disabling...
                                    </span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Create Location Modal --}}
        @if ($showCreateModal)
            <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
                aria-modal="true">
                {{-- Backdrop --}}
                <div class="fixed inset-0 transition-opacity bg-black/80" wire:click="closeModal" wire:key="create-modal-backdrop"></div>

                {{-- Modal Panel --}}
                <div class="flex min-h-full items-center justify-center p-4">
                    <div
                        class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all w-full max-w-lg"
                        wire:click.stop>
                        <div class="px-6 py-4 bg-white border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Create Location</h3>
                        </div>

                        <div class="px-6 py-4">
                    @csrf
                            <div class="space-y-4">
                                {{-- Location Name --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Location Name <span
                                            class="text-red-500">*</span></label>
                                    <input type="text" wire:model="create_location_name"
                                        class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="Enter location name">
                                    @error('create_location_name')
                                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                    @enderror
                                </div>

                                {{-- Logo Section (matching Settings pattern exactly) --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-3">Logo <span
                                            class="text-gray-400">(Optional)</span></label>

                                    <div class="space-y-3">
                                        <label
                                            class="cursor-pointer inline-flex items-center justify-center w-full px-4 py-2.5 bg-white border-2 border-dashed border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:border-blue-400 hover:bg-blue-50 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                                </path>
                                            </svg>
                                            Choose Image
                                            <input type="file" wire:model="create_logo" class="hidden"
                                                accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                                        </label>

                                        {{-- File Info --}}
                                        @if ($create_logo)
                                            <div
                                                class="flex items-center justify-between bg-white rounded-md px-3 py-2 border border-gray-200">
                                                <p class="text-sm text-gray-700 truncate flex-1"
                                                    title="{{ $create_logo->getClientOriginalName() }}">
                                                    {{ $create_logo->getClientOriginalName() }}
                                                </p>
                                                <button wire:click="clearLogo('create')" type="button"
                                                    class="ml-2 text-xs text-red-600 hover:text-red-800 font-medium">
                                                    Clear
                                                </button>
                                            </div>
                                        @endif

                                        {{-- Logo Preview --}}
                                        <div
                                            class="flex items-center justify-center bg-white rounded-lg p-3 border border-gray-200">
                                            @if ($create_logo)
                                                <img src="{{ $create_logo->temporaryUrl() }}" alt="Logo preview"
                                                    class="max-w-full max-h-28 object-contain">
                                            @else
                                                <div
                                                    class="w-full h-28 flex items-center justify-center bg-gray-50 rounded-md">
                                                    <span class="text-xs text-gray-400">No image selected</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    @error('create_logo')
                                        <span class="text-red-500 text-xs mt-2 block">{{ $message }}</span>
                                    @enderror
                                    <p class="text-xs text-gray-600 mt-3 leading-relaxed">
                                        Supported formats: JPEG, PNG, GIF, WebP (Max 15MB)
                                    </p>
                                </div>

                                {{-- Create Slip Toggle --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Allow Create Slip</label>
                                    <label class="relative inline-flex items-center cursor-pointer" x-data="{ createSlip: @entangle('create_create_slip').live }" x-init="createSlip = @js($create_create_slip ?? false)">
                                        <input type="checkbox" x-model="createSlip" :checked="createSlip" class="sr-only">
                                        <div class="w-11 h-6 rounded-full focus-within:outline-none focus-within:ring-4 focus-within:ring-blue-300 transition-colors duration-200 relative" :class="createSlip ? 'bg-blue-600' : 'bg-gray-200'">
                                            <div class="absolute top-[2px] left-[2px] bg-white border border-gray-300 rounded-full h-5 w-5 transition-transform duration-200" :class="createSlip ? 'translate-x-5' : 'translate-x-0'"></div>
                                        </div>
                                        <span class="ml-3 text-sm text-gray-700" x-text="createSlip ? 'Enabled - Guards can create slips on outgoing trucks' : 'Disabled - Guards cannot create slips on outgoing trucks'"></span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
                            <button wire:click="closeModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 cursor-pointer hover:cursor-pointer">
                                Cancel
                            </button>
                            <button wire:click.prevent="createLocation" wire:loading.attr="disabled" wire:target="createLocation"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed hover:cursor-pointer cursor-pointer">
                                <span wire:loading.remove wire:target="createLocation">Create</span>
                                <span wire:loading.inline-flex wire:target="createLocation" class="inline-flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Creating...
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
