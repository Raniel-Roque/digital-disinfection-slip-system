<div class="max-w-full bg-white border border-gray-200 rounded-xl shadow-sm p-4 m-4">

    {{-- Search + Filter + Sort --}}
    <div class="mb-4 flex items-center gap-3">

        {{-- Search Bar --}}
        <div class="relative w-full">
            <label class="sr-only">Search</label>
            <input type="text" wire:model.live="search"
                class="py-2 px-3 ps-9 block w-full border-gray-200 shadow-sm rounded-lg sm:text-sm 
                        focus:border-blue-500 focus:ring-blue-500"
                placeholder="Search...">
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
                @else bg-gray-500 hover:bg-gray-600 focus:ring-gray-500
                @endif">
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

        {{-- Create Button (Only for Outgoing) --}}
        @if ($type === 'outgoing')
            <x-buttons.submit-button wire:click="openCreateModal" color="blue">
                <div class="flex items-center gap-2">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Create</span>
                </div>
            </x-buttons.submit-button>
        @endif
    </div>

    {{-- Filter Modal --}}
    <x-modals.filter-modal>
        <x-slot name="filters">
            {{-- Status Filter --}}
            <div x-data="{
                open: false,
                options: @js($availableStatuses),
                selected: @entangle('filterStatus').live,
                placeholder: 'Select status',
                get displayText() {
                    if (this.selected === null || this.selected === undefined || this.selected === '') {
                        return this.placeholder;
                    }
                    const key = String(this.selected);
                    return this.options[key] || this.placeholder;
                },
                closeDropdown() {
                    this.open = false;
                },
                handleFocusIn(event) {
                    const target = event.target;
                    const container = $refs.statusDropdownContainer;
                    if (this.open && !container.contains(target)) {
                        if (target.tagName === 'INPUT' ||
                            target.tagName === 'SELECT' ||
                            target.tagName === 'TEXTAREA' ||
                            (target.tagName === 'BUTTON' && target.closest('[x-data]') && !container.contains(target.closest('[x-data]')))) {
                            this.closeDropdown();
                        }
                    }
                }
            }" x-ref="statusDropdownContainer" @click.outside="closeDropdown()"
                @focusin.window="handleFocusIn($event)">
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <button type="button" wire:click="$set('filterStatus', '')"
                        x-show="selected !== null && selected !== undefined && selected !== ''"
                        class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                        Clear
                    </button>
                </div>

                <div class="relative">
                    <button type="button" x-on:click="open = !open"
                        class="inline-flex justify-between w-full px-4 py-2 text-sm font-medium bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-orange-500"
                        :class="{ 'ring-2 ring-orange-500': open }">
                        <span :class="{ 'text-gray-400': selected === null || selected === undefined || selected === '' }">
                            <span x-text="displayText"></span>
                        </span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 ml-2 -mr-1 transition-transform"
                            :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M6.293 9.293a1 1 0 011.414 0L10 11.586l2.293-2.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>

                    <!-- Dropdown Menu -->
                    <div x-show="open" x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="absolute right-0 mt-2 w-full rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 p-1 space-y-1 z-50"
                        style="display: none;" x-cloak @click.stop>
                        <template x-for="[value, label] in Object.entries(options)" :key="value">
                            <a href="#"
                                @click.prevent="
                                    selected = String(value);
                                    closeDropdown();
                                "
                                class="block px-4 py-2 text-gray-700 hover:bg-gray-100 active:bg-orange-100 cursor-pointer rounded-md transition-colors"
                                :class="{
                                    'bg-orange-50 text-orange-700': selected !== null && selected !== undefined && String(selected) === String(value)
                                }">
                                <span x-text="label"></span>
                            </a>
                        </template>
                    </div>
                </div>
            </div>
        </x-slot>
    </x-modals.filter-modal>

    {{-- CREATE MODAL --}}
    <x-modals.slip-creation-modal show="showCreateModal" :trucks="$trucks" :locations="$locations" :drivers="$drivers"
        :truckOptions="$truckOptions" :locationOptions="$locationOptions" :driverOptions="$driverOptions" />

    {{-- Disinfection Slip Details Modal --}}
    <livewire:trucks.disinfection-slip />

    {{-- Card List --}}
    <div wire:poll class="space-y-3 pb-4">

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
            <div wire:click="$dispatch('open-disinfection-details', { id: {{ $slip->id }}, type: '{{ $type }}' })"
                class="flex justify-between items-center p-4 border-l-4 rounded-lg shadow-sm transition hover:shadow-md cursor-pointer {{ $statusMap[$status]['color'] }}">

                <div class="grid grid-cols-2 gap-y-2 text-sm">
                    <div class="font-semibold text-gray-600">Slip ID:</div>
                    <div class="text-gray-800">{{ $slip->slip_id }}</div>

                    <div class="font-semibold text-gray-600">Plate #:</div>
                    <div class="text-gray-800">{{ $slip->truck->plate_number }}</div>
                </div>

                {{-- Right Side --}}
                <div class="flex flex-col items-end">
                    {{-- Status Badge --}}
                    <span
                        class="px-3 py-1 text-xs font-semibold rounded-full
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
